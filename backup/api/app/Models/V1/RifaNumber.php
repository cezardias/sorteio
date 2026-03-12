<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

use App\Models\V1\{Rifas, Clients, RifaPay};
class RifaNumber extends Model {
    use HasFactory;

    protected $fillable = ['numbers', 'status', 'rifas_id', 'pay_id', 'client_id'];

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class, 'rifas_id', 'id');
    }
    public function client(): BelongsTo {
        return $this->belongsTo(Clients::class);
    }
    public function rifaPay(): BelongsTo {
        return $this->belongsTo(RifaPay::class, 'pay_id', 'id');
    }


    public static function lookingForNumber($number, $rifasId) {
        $participantes = self::with(['rifa', 'client'])->where('status', 1)->where('rifas_id', $rifasId)->get();
        $ganhador = null; // Inicializa a variável

        foreach ($participantes as $participante) {
            $numbersParticipante = json_decode($participante->numbers, true);

            // Verifica se $numbersParticipante é um array e realiza a busca
            if (is_array($numbersParticipante)) {
                $find = array_search($number, $numbersParticipante);

                if ($find !== false) { // array_search retorna false se não encontrar
                    $ganhador = $participante;
                    break;
                }
            }
        }

        return $ganhador; // Retorna o participante encontrado ou null
    }


    public static function getAllNumbersClient($id) {
        return self::where('client_id', $id)->where('status', 1)->get();
    }
    public static function countTotalNumber($id) {
       return  self::where('rifas_id', $id)->where('status', 1) ->whereRaw('JSON_VALID(numbers)')
       ->selectRaw('SUM(JSON_LENGTH(numbers)) as total')->first();
    }

    public static function cancelarCompra($id) {
        $data = self::where('pay_id', $id)->first();
        AwardedQuota::excluirBilhetePremiado($data->numbers, $data->rifas_id, $data->rifa_pays_id);
        return $data ->update(['status' => 2, 'numbers' => null]);
    }

    public static function aprovarCompra($id) {
        return self::where('pay_id', $id)->update(['status' => 1]);
    }

    public static function applyRifa($rifaPay) {
        return DB::transaction(function() use ($rifaPay) {
            // Verificar novamente se os números estão disponíveis no momento da execução
            // Isto é uma segurança extra além do lockForUpdate
            $numbers = self::generateUniqueNumbers($rifaPay);

            if (!$numbers) {
                // Retornar false se não houver números suficientes
                return false;
            }

       

            // Registrar os números como utilizados
            $resp = self::create([
                'pay_id' => $rifaPay->id,
                'rifas_id' => $rifaPay->rifas_id,
                'numbers' => json_encode($numbers),
                'client_id' => $rifaPay->client_id,
                'status' => 0, // Pendente de pagamento inicialmente
            ]);

            return $resp;
        }, 5); // 5 tentativas para resolver deadlocks
    }

    public static function addNumeroDefinidoClient($rifaPay, $numbers) {
        $numbers = json_encode([$numbers]);

        $resp = self::create([
            'pay_id' => $rifaPay->id,
            'rifas_id' => $rifaPay->rifas_id,
            'numbers' => $numbers,
            'client_id' => $rifaPay->client_id,
            'status' => 1,
        ]);

        $id = $resp->id;

        return $id;
    }

    public static function generateUniqueNumbers($payment) {
        return DB::transaction(function() use ($payment) {
            $maxNumbers = $payment->rifa->cota->qntd_cota;
            $numToGenerate = $payment->qntd_number;

            // Obter números já vendidos ou reservados
            $existingNumbersJson = self::where('rifas_id', $payment->rifas_id)
                ->whereIn('status', [0, 1])
                ->lockForUpdate()
                ->pluck('numbers')
                ->toArray();

            // Array para armazenar todos os números existentes
            $allExistingNumbers = [];

            // Converter cada entrada JSON e adicionar ao array de números existentes
            foreach ($existingNumbersJson as $jsonNumbers) {
                $decodedNumbers = json_decode($jsonNumbers, true);
                if (is_array($decodedNumbers)) {
                    foreach ($decodedNumbers as $num) {
                        $allExistingNumbers[] = (int)$num; // Converter para inteiro para garantir
                    }
                }
            }

            // Obter números bloqueados e imediatos
            $blockedNumbers = $payment->rifa->AwardedQuota()
                ->whereIn('status', ['bloqueada', 'resgatada'])
                ->lockForUpdate()
                ->pluck('number_cota')
                ->map(function($num) {
                    return (int)$num;
                })
                ->toArray();

            $immediateNumbers = $payment->rifa->AwardedQuota()
                ->where('status', 'imediato')
                ->lockForUpdate()
                ->pluck('number_cota')
                ->map(function($num) {
                    return (int)$num;
                })
                ->toArray();

            // Criar conjuntos de números para verificação rápida
            $existingSet = array_flip($allExistingNumbers);
            $blockedSet = array_flip($blockedNumbers);

            // Números imediatos disponíveis (que não foram vendidos)
            $validImmediateNumbers = [];
            foreach ($immediateNumbers as $num) {
                if (!isset($existingSet[$num])) {
                    $validImmediateNumbers[] = $num;
                }
            }
            $validImmediateNumbers = array_slice($validImmediateNumbers, 0, $numToGenerate);

            // Determinar números disponíveis para geração aleatória
            $availableNumbers = [];
            for ($i = 1; $i <= $maxNumbers; $i++) {
                if (!isset($existingSet[$i]) && !isset($blockedSet[$i])) {
                    $availableNumbers[] = $i;
                }
            }

            // Verificar se há números suficientes
            $remainingToGenerate = $numToGenerate - count($validImmediateNumbers);
            if (count($availableNumbers) < $remainingToGenerate) {
                return false;
            }

            // Gerar números aleatórios
            shuffle($availableNumbers);
            $randomNumbers = array_slice($availableNumbers, 0, $remainingToGenerate);

            // Combinar os números gerados
            $generatedNumbers = array_merge($validImmediateNumbers, $randomNumbers);

            // Formatar os números com zeros à esquerda
            $maxLength = (int) ceil(log10($maxNumbers));
            $formattedNumbers = array_map(function($number) use ($maxLength) {
                return str_pad($number, $maxLength, '0', STR_PAD_LEFT);
            }, $generatedNumbers);

            return $formattedNumbers;
        });
    }



    public static function getRankingRifa($id) {
        $result = self::where('status', 1)
            ->where('rifas_id', $id)
            ->select('client_id')
            ->selectRaw('SUM(CASE WHEN JSON_VALID(numbers) THEN JSON_LENGTH(numbers) ELSE 0 END) as total_numbers')
            ->with('client')
            ->groupBy('client_id')
            ->orderByRaw('SUM(CASE WHEN JSON_VALID(numbers) THEN JSON_LENGTH(numbers) ELSE 0 END) DESC')
            ->limit(3)
            ->get();


        return $result ?? false;
    }
    public static function getRankingRifaGeral() {
        $result = self::where('rifa_numbers.status', 1)
            ->join('rifa_pays', 'rifa_pays.id', '=', 'rifa_numbers.pay_id')
            ->select(
                'rifa_numbers.client_id',
                'rifa_numbers.rifas_id',
                'rifa_numbers.pay_id',
                'rifa_numbers.created_at'
            )
            ->selectRaw('
                SUM(CASE WHEN JSON_VALID(rifa_numbers.numbers) THEN JSON_LENGTH(rifa_numbers.numbers) ELSE 0 END) as total_numbers,
                SUM(rifa_pays.value) as total_value
            ')
            ->with(['client', 'rifa', 'rifaPay'])
            ->groupBy(['rifa_numbers.client_id', 'rifa_numbers.rifas_id'])
            ->orderByRaw('total_numbers DESC')
            ->paginate(20);

        return $result ?? false;
    }


    public static function getRankingRifaGeralFiltro($totalNumbers = null, $rifasId = null, $startDate = null, $endDate = null) {
        $query = self::where('rifa_numbers.status', 1)
            ->join('rifa_pays', 'rifa_pays.id', '=', 'rifa_numbers.pay_id') // Faz o JOIN correto
            ->select(
                'rifa_numbers.client_id',
                'rifa_numbers.rifas_id',
                'rifa_numbers.pay_id',
                'rifa_numbers.created_at'
            )
            ->selectRaw('
                SUM(CASE WHEN JSON_VALID(rifa_numbers.numbers) THEN JSON_LENGTH(rifa_numbers.numbers) ELSE 0 END) as total_numbers,
                SUM(rifa_pays.value) as total_value
            ')
            ->with(['client', 'rifa', 'rifaPay'])
            ->groupBy(['rifa_numbers.client_id', 'rifa_numbers.rifas_id'])
            ->orderByRaw('total_numbers DESC');

        // Filtros opcionais
        if (!empty($rifasId)) {
            $query->where('rifa_numbers.rifas_id', $rifasId);
        }

        if (!empty($totalNumbers)) {
            $query->havingRaw('total_numbers >= ?', [(int) $totalNumbers]);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('rifa_numbers.created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }



    public static function cancelRifaNumber($ids) {
        return self::with(['rifa'])->whereIn('pay_id', $ids)
        ->update(['status' => 2, 'numbers' => null]);
    }
    public static function approvedRifaNumber($ids) {
        return self::with(['rifa'])->whereIn('pay_id', $ids)
        ->update(['status' => 1]);
    }












}
