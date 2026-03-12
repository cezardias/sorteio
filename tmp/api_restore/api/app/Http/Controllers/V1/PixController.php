<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\ForbiddenRequestException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;

use App\Models\V1\{RifaNumbers, Clients, Cotas, Rifas};
use App\Services\{PaymentStatusService};






class PixController extends Controller
{
    private string $accessToken;
    private PaymentStatusService $paymentStatusService;

    public function __construct() {
        $this->accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
        $this->paymentStatusService = new PaymentStatusService();
    }

    private function getPackageName($packageId) {
        switch ($packageId) {
            case 1:
                return 'first';
            case 2:
                return 'second';
            case 3:
                return 'third';
            case 4:
                return 'fourth';
            case 5:
                return 'fifth';
            case 6:
                return 'sixth';
            default:
                throw new BadRequestException("Invalid packageId");
        }
    }

    private function getPrice($rifa, $packageId, $numbers) {
        if (isset($packageId)) {
            $packageName = $this->getPackageName($packageId);
            $packageNumbers = $packageName . '_pacote_numbers';
            $packageDiscount = $packageName . '_pacote_discount';
            $value = $rifa[$packageNumbers] * $rifa->price -
            $rifa[$packageNumbers] *
                $rifa->price *
              ($rifa[$packageDiscount] / 100);
            return (float) number_format((float)$value, 2, '.', '');
        }
        if (isset($numbers)) {
            return (float) number_format((float)$rifa->price * $numbers, 2, '.', '');
        }
    }

    private function createPayment($price) {
        MercadoPagoConfig::setAccessToken($this->accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        $request_options = new RequestOptions();
        $request_options->setCustomHeaders(["X-Idempotency-Key: ". uniqid()]);
        $createRequest = [
            "transaction_amount" => $price,
            "description" => "Comprando rifas",
            "payment_method_id" => "pix",
            // "date_of_expiration" => Carbon::now()->addMinutes(30),
            'notification_url' => 'https://api.vitaobarbeiro.com/api/v1/mercado-pago-payments',
            "payer" => [
                "email" => "crissmykel10@gmail.com",
            ]
        ];
        $mercadoPagoClient = new PaymentClient();
        $payment = $mercadoPagoClient->create($createRequest, $request_options);
        return $payment;
    }

    private function updateOlderPayments() {
        $date = Carbon::now()->subMinutes(30);

        Cotas::whereIn('payment_status', [Cotas::PENDING])->where('created_at', '<=', $date)->update(['payment_status' => Cotas::LOST_RESERVATION]);
    }

    private function getClient($phone, $name) {
        if (!isset($phone)) {
            throw new BadRequestException("Phone is required");
        }
        if (!isset($name)) {
            throw new BadRequestException("Name is required");
        }
        Log::info($name);
        return Clients::firstOrCreate(['phone' => $phone], ['phone' => $phone, 'name' => $name]);
    }

    private function rifaTotalNumbers($rifaId) {
        return RifaNumbers::where(['rifa_id' => $rifaId])->count();
    }

    private function validateRifaLeftNumbers($rifa, $numbersQuant) {
        $rifaId = $rifa->id;
        $rifaMaxNumbers = $rifa->rifa_numbers;
        $totalNumbers = $this->rifaTotalNumbers($rifaId);
        if ($totalNumbers + $numbersQuant > $rifaMaxNumbers) {
            $rifaLeftNumbersQuant = $rifaMaxNumbers - $totalNumbers;
            throw new ForbiddenRequestException("Quantidade restante de números: $rifaLeftNumbersQuant");
        }
    }

    private function generateNumbersForRifa($numbersQuant) {
        $notReserved = Cotas::LOST_RESERVATION;
        $nums = DB::select(
            "SELECT @row := @row + 1 AS nums FROM
            (select 0 union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) t,
            (select 0 union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) t2,
            (select 0 union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) t3,
            (select 0 union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) t4,
            (SELECT @row:=0) numbers
            HAVING nums NOT IN (SELECT number FROM rifa_numbers INNER JOIN cotas ON cotas.id = rifa_numbers.cota_id WHERE cotas.payment_status NOT IN ($notReserved))
            ORDER BY RAND()
            LIMIT $numbersQuant"
        );
        return $nums;
    }

    private function insertNums($nums, $rifaId, $clientId, $payment, $free) {
        $now = Carbon::now()->toDateTimeString();
        // Log::info($payment->id);
        $data = [];
        $cota = Cotas::create(['payment_id' => isset($payment->id) ? $payment->id : 0, 'payment_status' => $free ? Cotas::FREE : Cotas::PENDING, 'price' => $free ? 0 : "$payment->transaction_amount"]);
        for($index = 0; $index < count($nums); $index += 1) {
            $currentNumber = $nums[$index]->nums;
            array_push($data, ['client_id' => $clientId, 'number' => $currentNumber, 'rifa_id' => $rifaId, 'cota_id' => $cota->id, 'created_at'=> $now,
            'updated_at'=> $now
            ]);
        }
        RifaNumbers::insert($data);
    }

    private function getNumbersQuant($rifa, $packageId, $numbers) {
        if (isset($packageId)) {
            $packageName = $this->getPackageName($packageId);
            $packageNumbers = $packageName . '_pacote_numbers';
            return $rifa[$packageNumbers];
        }
        if (isset($numbers)) {
            return $numbers;
        }
    }

    private function getClientCurrentNumbersCount($rifa, $client) {
        $count = RifaNumbers::join('clients', 'rifa_numbers.client_id', '=', 'clients.id')->join('cotas', 'rifa_numbers.cota_id', '=', 'cotas.id')->where('cotas.payment_status', '<>', 9)->where('client_id', $client->id)->where('rifa_id', $rifa->id)->count();
        return $count;
    }

    public function index(Request $request)
    {
        $this->updateOlderPayments();
        try {
            $res = Cache::lock('criar-rifas')->block(10, function () use ($request) {
                $rifa = Rifas::find($request->id);
                if (!isset($rifa)) {
                    throw new ItemNotFoundException('Rifa not found!');
                }
                if ($rifa->winner_id) {
                    return response()->json(["success" => true, "data" => [ "rifaEnded" => true]], 200);
                }
                $numbersQuant = $this->getNumbersQuant($rifa, $request->packageId, $request->rifaNumbers);
                $this->validateRifaLeftNumbers($rifa, $numbersQuant);
                $price = $this->getPrice($rifa, $request->packageId, $request->rifaNumbers);
                $client = $this->getClient($request->phone, $request->name);
                $clientCountNumbers = $this->getClientCurrentNumbersCount($rifa, $client);
                if ($clientCountNumbers + $numbersQuant > $rifa->max_numbers) {
                    $canBuy = $rifa->max_numbers - $clientCountNumbers;
                    if ($canBuy <= 0) {
                        throw new ForbiddenRequestException("Você não pode mais comprar números");
                    }
                    throw new ForbiddenRequestException("Você só pode comprar mais ". $rifa->max_numbers - $clientCountNumbers ." números");
                }
                $free = false;
                if ($rifa->price == 0) {
                    $free = true;
                    $payment = ['id' => 0];
                } else {
                    $payment = $this->createPayment($price);
                }
                $nums = $this->generateNumbersForRifa($numbersQuant);
                $this->insertNums($nums, $rifa->id, $client->id, $payment, $free);
                if ($free) {
                    return response()->json(["success" => true, "data" => [ "freeRifa" => true]], 200);
                }
                return response()->json(["success" => true, "data" => [ "qrCode" => $payment->point_of_interaction->transaction_data->qr_code_base64, "hash" => $payment->point_of_interaction->transaction_data->qr_code ]], 200);
            });
            if (!$res instanceof JsonResponse) {
                Log::error('Not a JSON response. Actual response: '.$res);
                throw new Exception("Internal error");
            }
            return $res;
        } catch (MPApiException $e) {
            return response()->json(["success" => true, "data" => [
                "statusCode" => $e->getApiResponse()->getStatusCode(),
                "content" => $e->getApiResponse()->getContent()
            ]], 200);
        }
        catch (BadRequestException $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], 400);
        } catch (ForbiddenRequestException $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], 403);
        } catch (ItemNotFoundException $e) {
            return response()->json(["success" => false, "message" => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(["success" => false, "message" => "Internal Error"], 500);
        }
    }
}
