<?php
namespace App\Services;

use App\Models\{RewardTypes, RewardItems, RewardItemStock, RewardPasse, RewardRedemption};
use Illuminate\Support\Facades\DB;

class RewardEngineService
{
    /* ===================== Helpers de metadados e contagem ===================== */

    private function metaArr($raw): array {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') return json_decode($raw, true) ?: [];
        return [];
    }

    /** Quantas vezes o usuário já ganhou este item específico */
    private function userWinsForItem(int $userId, int $rifaId, int $typeId, int $itemId): int {
        return RewardRedemption::where([
            'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$typeId,
            'reward_item_id'=>$itemId,'outcome'=>'reward'
        ])->count();
    }

    /** Quantos prêmios (qualquer item) o usuário já ganhou neste tipo */
    private function userWinsTotal(int $userId, int $rifaId, int $typeId): int {
        return RewardRedemption::where([
            'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$typeId,'outcome'=>'reward'
        ])->count();
    }

    /** Layout 1..N (array com ['id','pos','type']) de itens ATIVOS na ordem de exibição */
    private function activeLayout($items): array {
        $act = $items->where('status','active')
            ->map(fn($i)=>['id'=>$i->id,'pos'=>$i->position_number,'type'=>$i->type])
            ->values()
            ->all();

        // normaliza posições nulas mantendo ordem de chegada
        $seq = 1;
        foreach ($act as &$row) { if (!is_int($row['pos'])) $row['pos'] = $seq; $seq++; }
        unset($row);

        usort($act, fn($a,$b)=>$a['pos'] <=> $b['pos']);

        // reindexa para 1..N
        $n=1; foreach ($act as &$row) { $row['pos'] = $n++; } unset($row);
        return $act;
    }

    /** Posição de um setor NÃO-prêmio (preferindo itens try_again) */
    private function pickNonPrizePos(array $layout): int {
        $tryPos = array_column(array_filter($layout, fn($r)=>$r['type']!=='reward'), 'pos');
        if ($tryPos) return $tryPos[random_int(0, count($tryPos)-1)];
        // se não há try_again explícito, pega qualquer posição que não seja reward (só por segurança)
        $all = array_column($layout, 'pos');
        $rewardPos = array_column(array_filter($layout, fn($r)=>$r['type']==='reward'), 'pos');
        $non = array_values(array_diff($all, $rewardPos));
        return $non ? $non[random_int(0, count($non)-1)] : 1;
    }

    /** Payload alinhado com o front: centro do setor (position é 1-based) */
    private function payloadForPosition(int $pos, int $activeCount): array {
        $slice = 360 / max(1, $activeCount);
        $center = ($pos - 0.5) * $slice;
        return ['angle'=>$center, 'position'=>$pos];
    }

    /** Sorteio ponderado */
    private function weightedPick($items) {
        if ($items->isEmpty()) return null;
        $sum=0; $acc=[];
        foreach ($items as $it) {
            $w = max(1,(int)$it->weight);
            $sum += $w;
            $acc[] = ['it'=>$it,'ceil'=>$sum];
        }
        $r = random_int(1,$sum);
        foreach ($acc as $slot) if ($r <= $slot['ceil']) return $slot['it'];
        return $items->first();
    }

    /* ===================== Concessão de passes (sem mudanças) ===================== */

    public function grantByPurchase(int $userId, int $rifaId, int $numbersBought, string $source): void
    {
        if ($numbersBought < 1) return;

        DB::transaction(function() use ($userId,$rifaId,$numbersBought,$source) {
            if (RewardPasse::where('source',$source)->exists()) return;

            $types = RewardTypes::where(['rifas_id'=>$rifaId,'is_active'=>true])->get();
            foreach ($types as $t) {
                $meta = $this->metaArr($t->metadata);
                $per  = (int)($meta['numbers_per_pass'] ?? 0);
                if ($per < 1) continue;

                $passes = intdiv($numbersBought, $per);
                if ($passes < 1) continue;

                $row = RewardPasse::firstOrCreate(
                    ['user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$t->id],
                    ['balance'=>0,'source'=>$source]
                );
                $row->increment('balance', $passes);
            }
        });
    }

    /* ===================== Core: Redeem determinístico e genérico ===================== */

    public function redeem(int $userId, int $rifaId, string $typeCode, ?string $idemp = null): array
    {
        return DB::transaction(function() use ($userId,$rifaId,$typeCode,$idemp) {

            $type = RewardTypes::where([
                'rifas_id'=>$rifaId,'code'=>$typeCode,'is_active'=>true
            ])->firstOrFail();

            // idempotência
            if ($idemp) {
                $exists = RewardRedemption::where('idempotency_key',$idemp)->first();
                if ($exists) return $exists->toArray();
            }

            // trava e valida passe
            $pass = RewardPasse::where([
                'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id
            ])->lockForUpdate()->first();

            if (!$pass || $pass->balance < 1) {
                throw new \RuntimeException('Sem passes disponíveis');
            }

            // limite global de giros por usuário (opcional)
            if ($type->giro_por_usuario !== null) {
                $made = RewardRedemption::where([
                    'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id
                ])->count();
                if ($made >= $type->giro_por_usuario) {
                    throw new \RuntimeException('Limite de giros atingido');
                }
            }

            $typeMeta = $this->metaArr($type->metadata);
            $maxWinsPerItemDefault = (int)($typeMeta['max_wins_per_item'] ?? 1);                // DEFAULT: 1
            $maxTotalRewardsUser   = isset($typeMeta['max_total_rewards_per_user'])
                ? (int)$typeMeta['max_total_rewards_per_user'] : null;                          // opcional

            // trava total por usuário (se configurado)
            if ($maxTotalRewardsUser !== null) {
                $wonTotal = $this->userWinsTotal($userId, $rifaId, $type->id);
                if ($wonTotal >= $maxTotalRewardsUser) {
                    // consome passe e força try_again
                    $items = RewardItems::where(['rifas_id'=>$rifaId,'reward_type_id'=>$type->id])->get();
                    $layout = $this->activeLayout($items);
                    $activeCount = max(1, count($layout));
                    $pos = $this->pickNonPrizePos($layout);

                    $pass->decrement('balance',1);
                    return RewardRedemption::create([
                        'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id,
                        'outcome'=>'try_again','consumed_passes'=>1,
                        'animation_payload'=>$this->payloadForPosition($pos, $activeCount),
                        'idempotency_key'=>$idemp
                    ])->toArray();
                }
            }

            // carrega itens e layout
            $items  = RewardItems::where(['rifas_id'=>$rifaId,'reward_type_id'=>$type->id])->get();
            $layout = $this->activeLayout($items);
            $activeCount = max(1, count($layout));

            $totalTries = RewardRedemption::where([
                'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id
            ])->count();

            /* -------------------- 1) Pool base de prêmios elegíveis -------------------- */
            $eligibleRewards = $items->filter(function($it) use ($userId,$rifaId,$type,$totalTries,$maxWinsPerItemDefault) {
                if ($it->status!=='active' || $it->type!=='reward') return false;

                // min de tentativas p/ liberar
                if ($it->min_tries_required && ($totalTries+1) < (int)$it->min_tries_required) return false;

                // estoque (se existir)
                $st = RewardItemStock::where('reward_item_id',$it->id)->first();
                if ($st && $st->remaining < 1) return false;

                // limite por usuário (item → override, tipo → default)
                $itemMeta = $this->metaArr($it->metadata);
                $maxWinsItem = (int)($itemMeta['max_wins_per_user'] ?? $maxWinsPerItemDefault); // 0 = ilimitado
                if ($maxWinsItem > 0) {
                    $userWinsThis = $this->userWinsForItem($userId, $rifaId, $type->id, $it->id);
                    if ($userWinsThis >= $maxWinsItem) return false;
                }

                return true;
            })->values();

            /* Se **não há** prêmios elegíveis → força TRY_AGAIN em setor não-prêmio */
            if ($eligibleRewards->isEmpty()) {
                $pos = $this->pickNonPrizePos($layout);
                $pass->decrement('balance',1);

                return RewardRedemption::create([
                    'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id,
                    'outcome'=>'try_again','consumed_passes'=>1,
                    'animation_payload'=>$this->payloadForPosition($pos, $activeCount),
                    'idempotency_key'=>$idemp
                ])->toArray();
            }

            /* -------------------- 2) Itens "immediate" elegíveis -------------------- */
            $immediate = $eligibleRewards->first(function($it) use ($totalTries) {
                return $it->status === 'immediate'
                    && (!$it->min_tries_required || ($totalTries+1) >= (int)$it->min_tries_required);
            });

            $chosen = $immediate ?: null;

            /* -------------------- 3) Pity: guarantee_after -------------------- */
            if (!$chosen) {
                $fails = RewardRedemption::where([
                    'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id
                ])->whereIn('outcome',['try_again','empty'])->count();

                $minPity = $eligibleRewards->pluck('guarantee_after')->filter()->min();
                $shouldGuarantee = $minPity && ($fails+1 >= (int)$minPity);

                if ($shouldGuarantee) {
                    $pool = $eligibleRewards->filter(function($it) use ($totalTries){
                        if ($it->min_tries_required && ($totalTries+1) < (int)$it->min_tries_required) return false;
                        return true;
                    })->values();

                    if ($pool->isNotEmpty()) {
                        $chosen = $this->weightedPick($pool);
                    }
                }
            }

            /* -------------------- 4) Sorteio normal -------------------- */
            if (!$chosen) {
                $chosen = $this->weightedPick($eligibleRewards);
            }

            /* -------------------- 5) Consome passe -------------------- */
            $pass->decrement('balance',1);

            /* -------------------- 6) Baixa estoque (se houver) -------------------- */
            if ($chosen && $chosen->type==='reward') {
                $stock = RewardItemStock::where('reward_item_id',$chosen->id)->lockForUpdate()->first();
                if ($stock && $stock->remaining>0) $stock->decrement('remaining',1);
            }

            /* -------------------- 7) Payload (position determinística) -------------------- */
            $pos = $chosen?->position_number ?? null;
            if (!is_int($pos) || $pos < 1 || $pos > $activeCount) {
                $hit = array_values(array_filter($layout, fn($r)=>$r['id']===($chosen->id ?? -1)));
                $pos = $hit[0]['pos'] ?? 1;
            }
            $payload = $this->payloadForPosition($pos, $activeCount);

            $outcome = $chosen
                ? ($chosen->type==='reward' ? 'reward' : 'try_again')
                : 'try_again';

            $red = RewardRedemption::create([
                'user_id'=>$userId,'rifas_id'=>$rifaId,'reward_type_id'=>$type->id,
                'reward_item_id'=>$chosen?->id,'outcome'=>$outcome,'consumed_passes'=>1,
                'animation_payload'=>$payload,
                'idempotency_key'=>$idemp
            ]);

            $arr = $red->toArray();
            $arr['label'] = $chosen?->text;
            return $arr;
        });
    }
}
