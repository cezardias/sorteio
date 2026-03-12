<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRifaNumbers;
use App\Models\V1\RifaNumber;
use App\Models\V1\RifaPay;
use App\Models\GanhoAfiliado;
use Illuminate\Console\Command;
use App\Services\PaymentService;
use App\Services\RewardEngineService;
use Exception;

class VerifyPayment extends Command
{
    protected $signature = 'pagamentos:verificar';
    protected $description = 'Verificar pagamentos a cada minuto';

    public function __construct(
        protected PaymentService $paymentService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->info('Verificando pagamentos...');
            [$cancelPayIds, $payMadeIds] = $this->paymentService->verifyPayments();

            if (empty($cancelPayIds) && empty($payMadeIds)) {
                $this->info('Não há pagamentos pendentes para verificar.');
                return Command::SUCCESS;
            }

            if (!empty($cancelPayIds)) {
                RifaNumber::cancelRifaNumber($cancelPayIds);
                GanhoAfiliado::cancelPagamentoAfiliado($cancelPayIds);
                $this->info('Pagamentos cancelados: ' . implode(', ', $cancelPayIds));
            }

            if (!empty($payMadeIds)) {
                // 1) marca os números como aprovados
                RifaNumber::approvedRifaNumber($payMadeIds);

                // 2) concede PASSES para cada pagamento aprovado
                foreach ($payMadeIds as $payId) {
                    $pay = RifaPay::find($payId);
                    if (!$pay) {
                        $this->warn("Pagamento {$payId} não encontrado para conceder passes.");
                        continue;
                    }

                    // quantidade de números comprados
                    $numbersBought = (int) ($pay->qntd_number ?? 0);
                    if ($numbersBought <= 0) {
                        // fallback: conta registros em rifa_numbers vinculados ao pagamento
                        $numbersBought = (int) RifaNumber::where('pay_id', $pay->id)->count();
                    }

                    if ($numbersBought > 0) {
                        // idempotência via 'source': evita duplicar passes se outro fluxo também conceder
                        app(RewardEngineService::class)->grantByPurchase(
                            userId:        $pay->client_id,
                            rifaId:        $pay->rifas_id,
                            numbersBought: $numbersBought,
                            source:        "purchase:{$pay->id}"
                        );
                        $this->info("Passes concedidos (pagamento {$pay->id}) — números: {$numbersBought}");
                    } else {
                        $this->warn("Pagamento {$pay->id} sem números para conceder passes.");
                    }
                }

                $this->info('Pagamentos aprovados: ' . implode(', ', $payMadeIds));
            }

            $this->info('Verificação de pagamentos concluída.');
            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('Ocorreu um erro durante a verificação de pagamentos: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
