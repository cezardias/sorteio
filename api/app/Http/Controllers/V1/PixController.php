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

use App\Models\V1\{RifaNumber, Clients, Rifas, RifaPay};
use App\Services\{PaymentStatusService, EscaleCyberService};

class PixController extends Controller
{
    private EscaleCyberService $escaleCyberService;
    private PaymentStatusService $paymentStatusService;

    public function __construct(EscaleCyberService $escaleCyberService)
    {
        $this->escaleCyberService = $escaleCyberService;
        $this->paymentStatusService = new PaymentStatusService();
    }

    private function getPackageName($packageId)
    {
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

    private function getPrice($rifa, $packageId, $numbers)
    {
        if (isset($packageId)) {
            $packageName = $this->getPackageName($packageId);
            $packageNumbers = $packageName . '_pacote_numbers';
            $packageDiscount = $packageName . '_pacote_discount';
            $value = $rifa[$packageNumbers] * $rifa->price -
                $rifa[$packageNumbers] * $rifa->price * ($rifa[$packageDiscount] / 100);
            return (float) number_format((float) $value, 2, '.', '');
        }
        if (isset($numbers)) {
            return (float) number_format((float) $rifa->price * $numbers, 2, '.', '');
        }
        return 0;
    }

    private function updateOlderPayments()
    {
        $date = Carbon::now()->subMinutes(1440);
        RifaPay::where('status', 0)->where('created_at', '<=', $date)->update(['status' => 2]);
        RifaNumber::where('status', 0)->where('created_at', '<=', $date)->update(['status' => 2]);
    }

    private function getClient($phone, $name, $cpf = null, $email = null)
    {
        if (!isset($phone))
            throw new BadRequestException("Phone is required");
        if (!isset($name))
            throw new BadRequestException("Name is required");
        return Clients::firstOrCreate(['phone' => $phone], [
            'phone' => $phone,
            'name' => $name,
            'cpf' => $cpf,
            'email' => $email
        ]);
    }

    private function rifaTotalNumbers($rifaId)
    {
        return DB::table('rifa_numbers')
            ->where('rifas_id', $rifaId)
            ->whereIn('status', [0, 1])
            ->sum(DB::raw('JSON_LENGTH(numbers)'));
    }

    private function validateRifaLeftNumbers($rifa, $numbersQuant)
    {
        $rifaId = $rifa->id;
        $rifaMaxNumbers = $rifa->rifa_numbers;
        $totalNumbers = $this->rifaTotalNumbers($rifaId);
        if ($totalNumbers + $numbersQuant > $rifaMaxNumbers) {
            $rifaLeftNumbersQuant = $rifaMaxNumbers - $totalNumbers;
            throw new ForbiddenRequestException("Insufficient numbers. Left: $rifaLeftNumbersQuant");
        }
    }

    public function index(Request $request)
    {
        $this->updateOlderPayments();
        try {
            $res = Cache::lock('criar-rifas')->block(10, function () use ($request) {
                $rifa = Rifas::find($request->id);
                if (!$rifa)
                    throw new ItemNotFoundException('Rifa not found!');
                if ($rifa->winner_id)
                    return response()->json(["success" => true, "data" => ["rifaEnded" => true]], 200);

                $numbersQuant = $this->getNumbersQuant($rifa, $request->packageId, $request->rifaNumbers);
                $this->validateRifaLeftNumbers($rifa, $numbersQuant);
                $price = $this->getPrice($rifa, $request->packageId, $request->rifaNumbers);
                $client = $this->getClient($request->phone, $request->name, $request->cpf, $request->email);

                $free = ($price == 0);

                return DB::transaction(function () use ($rifa, $client, $price, $free, $numbersQuant) {
                    $cod = rand(100000, 999999);
                    $checkout = substr(md5(uniqid()), 0, 12);

                    $rifaPay = RifaPay::create([
                        'value' => $price,
                        'qntd_number' => $numbersQuant,
                        'status' => $free ? 1 : 0,
                        'verify' => 0,
                        'cod' => $cod,
                        'checkout' => $checkout,
                        'rifas_id' => $rifa->id,
                        'client_id' => $client->id,
                    ]);

                    $payment = ['transaction_id' => null, 'amount' => $price, 'qr_code' => null, 'qr_code_base64' => null];

                    if (!$free) {
                        $paymentResult = $this->escaleCyberService->createPixTransaction([
                            'amount' => $price,
                            'customerName' => $client->name,
                            'customerEmail' => $client->email ?? 'cliente@exemplo.com',
                            'customerPhone' => $client->phone,
                            'customerDocument' => $client->cpf ?? '00000000000',
                            'description' => "Compra de rifas: {$rifa->title}",
                            'metadata' => [
                                'rifa_id' => $rifa->id,
                                'client_id' => $client->id,
                                'rifa_pay_id' => $rifaPay->id
                            ]
                        ]);

                        if (!$paymentResult['success'])
                            throw new Exception($paymentResult['message']);
                        $payment = $paymentResult;

                        $rifaPay->update([
                            'pix_id' => $payment['transaction_id'],
                            'qr_code' => $payment['qr_code'],
                            'qr_code_base64' => $payment['qr_code_base64']
                        ]);
                    }

                    $rifaPay->rifa = $rifa;
                    $generatedNums = RifaNumber::generateUniqueNumbers($rifaPay);

                    if (!$generatedNums)
                        throw new Exception("Could not generate unique numbers.");

                    RifaNumber::create([
                        'pay_id' => $rifaPay->id,
                        'rifas_id' => $rifa->id,
                        'numbers' => json_encode($generatedNums),
                        'client_id' => $client->id,
                        'status' => $free ? 1 : 0
                    ]);

                    if ($free)
                        return response()->json(["success" => true, "data" => ["freeRifa" => true]], 200);

                    return response()->json([
                        "success" => true,
                        "data" => [
                            "qrCode" => $payment['qr_code_base64'],
                            "hash" => $payment['qr_code'],
                            "transaction_id" => $payment['transaction_id']
                        ]
                    ], 200);
                });
            });

            return $res;
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(["success" => false, "message" => $e->getMessage()], 500);
        }
    }

    private function getNumbersQuant($rifa, $packageId, $numbers)
    {
        if (isset($packageId)) {
            $packageName = $this->getPackageName($packageId);
            return $rifa[$packageName . '_pacote_numbers'];
        }
        return $numbers ?? 0;
    }
}
