<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ItemNotFoundException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

use App\Models\V1\{Cotas};
class MercadoPagoController extends Controller
{
    private string $accessToken;

    public function __construct() {
        $this->accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
    }

    public function getQr(Request $request) {
        try {
            $cotaId = $request->cotaId;
            if (!isset($cotaId)) {
                throw new BadRequestException("cotaId is required");
            }
            $cota = Cotas::find($cotaId);
            if (!$cota) {
                throw new ItemNotFoundException("Cota not found");
            }
            $id = $cota->payment_id;
            $response = Http::withoutVerifying()->withHeaders(['Authorization' => 'Bearer '.$this->accessToken, 'Accept' => 'application/json'])->get('https://api.mercadopago.com/v1/payments/'.$id);
            $payment = json_decode($response);
            if (isset($payment->status) && $payment->status === 404) {
                throw new ItemNotFoundException("Payment not found");
            }
            if (isset($payment->status) && $payment->status === 500) {
                throw new Exception("Internal Error");
            }
            return response()->json(['success' => true,'data' => ["qrCode" => $payment->point_of_interaction->transaction_data->qr_code_base64, "hash" => $payment->point_of_interaction->transaction_data->qr_code,]], 200);
        } catch (BadRequestException $e) {
            return response()->json(['success' => false, "data" => [ 'msg' => $e->getMessage() ]], 400);
        } catch (ItemNotFoundException $e) {
            return response()->json(['success' => false, "data" => [ 'msg' => $e->getMessage() ]], 404);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, "data" => [ 'msg' => 'Internal Error' ]], 500);
        }
    }

    public function index(Request $request)
    {
        $id = $request->data['id'];
        $response = Http::withoutVerifying()->withHeaders(['Authorization' => 'Bearer '.$this->accessToken, 'Accept' => 'application/json'])->get('https://api.mercadopago.com/v1/payments/'.$id);
        $payment = json_decode($response);
        if (isset($payment->status)) {
            switch ($payment->status) {
                case 'pending':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::PENDING]);
                    break;
                case 'approved':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::APPROVED]);
                    break;
                case 'authorized':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::AUTHORIZED]);
                    break;
                case 'in_process':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::IN_PROCESS]);
                    break;
                case 'in_mediation':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::IN_MEDIATION]);
                    break;
                case 'rejected':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::REJECTED]);
                    break;
                case 'cancelled':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::CANCELLED]);
                    break;
                case 'refunded':
                    Cotas::where('payment_id', $id)->update(['payment_status' => Cotas::REFUNDED]);
                    break;
                default:
                    break;
            }
        }
        return response()->json(["success" => true], 200);
    }
}
