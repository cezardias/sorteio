<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\V1\{RifaPay, RifaNumber, AwardedQuota};
use App\Services\RewardEngineService;

class EscaleCyberWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Escale Cyber Webhook Received', $request->all());

        $payload = $request->all();

        // Webhook structure according to docs: { "id": "evt_...", "type": "...", "data": { "status": "APPROVED", "id": "txn_...", "metadata": { ... } } }
        $type = $payload['type'] ?? null;
        $data = $payload['data'] ?? [];

        $transactionId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $rifaPayId = $metadata['rifa_pay_id'] ?? null;

        if (!$rifaPayId && $transactionId) {
            $rifaPay = RifaPay::where('pix_id', $transactionId)->first();
        } elseif ($rifaPayId) {
            $rifaPay = RifaPay::find($rifaPayId);
        } else {
            return response()->json(['message' => 'Transaction identifiers not found'], 400);
        }

        if (!$rifaPay) {
            Log::warning("EscaleCyber Webhook: RifaPay record not found for transaction {$transactionId}");
            return response()->json(['message' => 'RifaPay record not found'], 404);
        }

        if ($status === 'APPROVED' || $type === 'pix.in.confirmation') {
            if ($rifaPay->status != 1) {
                DB::transaction(function () use ($rifaPay) {
                    $rifaPay->update(['status' => 1]);
                    RifaNumber::where('pay_id', $rifaPay->id)->update(['status' => 1]);

                    $numbersRecord = RifaNumber::where('pay_id', $rifaPay->id)->first();
                    $numbers = $numbersRecord ? json_decode($numbersRecord->numbers, true) : [];

                    AwardedQuota::ganhadorBilhetePremiado(json_encode($numbers), $rifaPay->client_id, $rifaPay->rifas_id, $rifaPay->id);

                    // Grant rewards
                    app(RewardEngineService::class)->grantByPurchase(
                        userId: $rifaPay->client_id,
                        rifaId: $rifaPay->rifas_id,
                        numbersBought: $rifaPay->qntd_number,
                        source: "purchase:{$rifaPay->id}"
                    );
                });
                Log::info("Payment APPROVED via Webhook for RifaPay: {$rifaPay->id}");
            }
        } elseif ($status === 'EXPIRED' || $status === 'FAILED' || $status === 'CANCELLED') {
            if ($rifaPay->status == 0) {
                $rifaPay->update(['status' => 2]);
                RifaNumber::where('pay_id', $rifaPay->id)->update(['status' => 2]);
                Log::info("Payment {$status} via Webhook for RifaPay: {$rifaPay->id}");
            }
        }

        return response()->json(['success' => true]);
    }
}
