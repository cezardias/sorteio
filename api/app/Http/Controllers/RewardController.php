<?php

namespace App\Http\Controllers;

use App\Services\RewardEngineService;
use Illuminate\Support\Facades\DB;
use App\Models\RewardItems;
use App\Models\RewardTypes;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function addPremios(Request $request) {
        try {
            DB::beginTransaction();

            $rifas_id = (int) $request->rifas_id;

            $existingTypeCodes = array_map(fn($t) => $t['code'], $request->reward_types ?? []);
            RewardTypes::where('rifas_id', $rifas_id)
                ->whereNotIn('code', $existingTypeCodes)
                ->delete();

            $rewardTypeMap = [];
            foreach ($request->reward_types ?? [] as $type) {
                $rewardType = RewardTypes::updateOrCreate(
                    ['code' => $type['code'], 'rifas_id' => $rifas_id],
                    [
                        'name'             => $type['name'],
                        'is_active'        => (bool) $type['is_enabled'],
                        'giro_por_usuario' => (int) ($type['giro_por_usuario'] ?? 0),
                        'metadata'         => $type['metadata'] ?? null, // {numbers_per_pass}
                    ]
                );
                $rewardTypeMap[$type['code']] = $rewardType->id;
            }

            // remove todos os prêmios antigos desta rifa (simples e direto)
            RewardItems::where('rifas_id', $rifas_id)->delete();

            // cria novos
            foreach ($request->reward_items ?? [] as $item) {
                RewardItems::create([
                    'text'                => $item['text'],
                    'type'                => $item['type'], // reward | try_again
                    'position_number'     => $item['position_number'] ?? null,
                    'min_tries_required'  => $item['min_tries_required'] ?? null,
                    'guarantee_after'     => $item['guarantee_after'] ?? null,
                    'weight'              => $item['weight'] ?? 1,
                    'status'              => $item['status'], // active | blocked | immediate
                    'metadata'            => json_encode($item['metadata'] ?? []),
                    'rifas_id'            => $rifas_id,
                    'reward_type_id'      => $rewardTypeMap[$item['reward_type_code']] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'msg' => 'Prêmios salvos com sucesso.']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    // Tipos ativos para a página pública
    public function config($rifa) {
        $rows = RewardTypes::where('rifas_id', $rifa)
            ->get(['code','name','is_active','giro_por_usuario']);
        return response()->json(['data' => $rows]);
    }

    // Saldos de passes do usuário logado
    public function balances(Request $request, int $rifa) {
        // return [$request->user(), $request->all(), $rifa];
        $userId = optional($request->user())->id;

        // 2) se não, tenta ?cid= (id do cliente)
        if (!$userId) {
            $cid = (int) $request->query('cid');
            if ($cid > 0) $userId = $cid;
        }

        // 3) sem dono => visitante
        if (!$userId) return response()->json(['data' => []]);

        // JOIN garante que só venham tipos ativos da rifa correta
        $rows = \DB::table('reward_passes as p')
            ->join('reward_types as t', 't.id', '=', 'p.reward_type_id')
            ->where('p.user_id', $userId)
            ->where('p.rifas_id', $rifa)
            ->where('t.rifas_id', $rifa)
            ->where('t.is_active', true)
            ->get([
                \DB::raw('t.code as reward_type_code'),
                'p.balance'
            ]);

        return response()->json(['data' => $rows]);
    }



    // Resgatar (girar/abrir)
    public function redeem(Request $request, int $rifa, string $type, RewardEngineService $svc)
    {
        $user = $request->user();
        if (!$user) return response()->json(['msg'=>'Não autenticado'],401);

        $idemp = $request->header('Idempotency-Key') ?: null;
        try {
            $ret = $svc->redeem($user->id, $rifa, $type, $idemp);
            if (!($ret['ok'] ?? false)) {
                return response()->json(['msg'=>$ret['error'] ?? 'Falha no resgate'], 422);
            }
            return response()->json($ret['data']);
        } catch (\Throwable $e) {
            return response()->json(['msg'=>$e->getMessage()], 500);
        }
    }

    // Admin pega config completa para montar tela (itens por tipo)
    public function getConfigPremios($rifa) {
        $types = RewardTypes::where('rifas_id', $rifa)
            ->get(['id','code','name','is_active','giro_por_usuario','metadata']);

        $items = RewardItems::where('rifas_id', $rifa)
            ->get(['id','text','type','position_number','min_tries_required','guarantee_after','weight','status','metadata','reward_type_id']);

        $typeIdToCode = $types->pluck('code','id');

        $outTypes = $types->map(function($t){
            $meta = $t->metadata;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
            return [
                'id'               => $t->id,
                'code'             => $t->code,
                'name'             => $t->name,
                'is_active'        => (bool)$t->is_active,
                'giro_por_usuario' => (int)$t->giro_por_usuario,
                'metadata'         => $meta ?? null, // ['numbers_per_pass'=>N]
            ];
        })->values();

        $groupedItems = [];
        foreach ($items as $it) {
            $code = $typeIdToCode[$it->reward_type_id] ?? null;
            if (!$code) continue;

            $meta = $it->metadata;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }

            $groupedItems[$code][] = [
                'id'                 => $it->id,
                'text'               => $it->text,
                'type'               => $it->type, // reward | try_again
                'position_number'    => $it->position_number,
                'min_tries_required' => $it->min_tries_required,
                'guarantee_after'    => $it->guarantee_after,
                'weight'             => $it->weight,
                'status'             => $it->status, // active | blocked | immediate
                'metadata'           => $meta ?? [],
            ];
        }

        $groupedItems = array_merge(['wheel'=>[], 'chest'=>[]], $groupedItems);
        $general = ['is_active' => $types->contains('is_active', true)];

        return response()->json(['types'=>$outTypes, 'items'=>$groupedItems, 'general'=>$general]);
    }

    // RESUMO para o cliente ver passes e prêmios ganhos
    public function summary(Request $request, int $rifa) {
        // Pode ser autenticado OU via ?cid=
        $userId = optional($request->user())->id ?: (int) $request->query('cid');

        if (!$userId) {
            return response()->json([
                'programs'=>[],
                'prizes'=>[],
                'totals'=>['prizesCount'=>0]
            ]);
        }

        // Tipos ativos
        $types = RewardTypes::where(['rifas_id'=>$rifa,'is_active'=>true])->get(['id','code']);
        if ($types->isEmpty()) {
            return response()->json([
                'programs'=>[],
                'prizes'=>[],
                'totals'=>['prizesCount'=>0]
            ]);
        }

        // Passes por tipo (JOIN para não depender de mapeio manual)
        $passes = \DB::table('reward_passes as p')
            ->join('reward_types as t','t.id','=','p.reward_type_id')
            ->where('p.user_id',$userId)
            ->where('p.rifas_id',$rifa)
            ->where('t.rifas_id',$rifa)
            ->where('t.is_active',true)
            ->get(['t.code as code','p.balance']);

        $programs = [];
        foreach ($types as $t) {
            $hit = $passes->firstWhere('code', $t->code);
            $programs[$t->code] = [
                'isActive'        => true,
                'availablePasses' => (int) ($hit->balance ?? 0),
            ];
        }

        // Prêmios ganhos (se existirem). Obs: se os resgates só acontecem logado,
        // clientes sem login provavelmente verão vazio aqui — não tem problema.
        $reds = RewardRedemption::with('rewardItem')
            ->where(['user_id'=>$userId,'rifas_id'=>$rifa])
            ->where('outcome','reward')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $mapIdToCode = $types->pluck('code','id');
        $prizes = $reds->map(function($r) use ($mapIdToCode) {
            return [
                'attemptId' => $r->id,
                'type'      => $mapIdToCode[$r->reward_type_id] ?? 'wheel',
                'label'     => $r->rewardItem->text ?? 'Prêmio',
                'itemId'    => $r->reward_item_id,
                'awardedAt' => optional($r->created_at)->toISOString(),
                'payload'   => $r->animation_payload ?? null,
            ];
        })->values()->all();

        return response()->json([
            'programs' => $programs,
            'prizes'   => $prizes,
            'totals'   => ['prizesCount'=>count($prizes)],
        ]);
    }



}
