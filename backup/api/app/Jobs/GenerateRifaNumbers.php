<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

use App\Models\V1\RifaNumber;
use App\Models\V1\RifaPay;
use Exception;

class GenerateRifaNumbers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request;
    protected $rifaPayId;

    public function __construct(array $data, $rifaPayId)
    {
        $this->request = (object) $data; // opcional, se você precisar acessar como objeto
        $this->rifaPayId = $rifaPayId;
    }

    public function handle()
{
    DB::transaction(function () {
        $payment = RifaPay::with(['rifa.cota', 'rifa.AwardedQuota'])
            ->where('id', $this->rifaPayId)
            ->lockForUpdate()
            ->first();

        if (!$payment) {
            throw new Exception("Pagamento não encontrado.");
        }

        if (!$payment->rifa || !$payment->rifa->cota) {
            throw new Exception("Rifa ou Cota não encontrada.");
        }

        $numbers = RifaNumber::generateUniqueNumbers($payment);

        if (!$numbers['success']) {
            throw new Exception($numbers['message']);
        }

        RifaNumber::create([
            'pay_id' => $this->rifaPayId,
            'rifas_id' => $this->request->rifas_id,
            'numbers' => json_encode($numbers['numbers']),
            'client_id' => $this->request->client_id,
        ]);
    });
}


}
