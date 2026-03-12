<?php

namespace App\Http\Controllers;

use App\Models\RewardItems;
use App\Models\RewardTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardAdminController extends Controller
{
    public function show($rifa) {
        $types = RewardTypes::where('rifas_id', $rifa)->get();
        $items = RewardItems::where('rifas_id', $rifa)->get();
        return response()->json(['types' => $types, 'items' => $items]);
    }

    public function store(Request $r, $rifa) {
        $data = $r->validate([
            'types' => 'nullable|array',
            'types.*.code' => 'required|string',
            'types.*.name' => 'required|string',
            'types.*.is_active' => 'boolean',
            'types.*.giro_por_usuario' => 'nullable|integer|min:0',
            'types.*.metadata' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.text' => 'required|string',
            'items.*.type' => 'required|in:reward,try_again',
            'items.*.status' => 'required|in:active,blocked,immediate',
            'items.*.position_number' => 'nullable|integer|min:0',
            'items.*.min_tries_required' => 'nullable|integer|min:0',
            'items.*.guarantee_after' => 'nullable|integer|min:0',
            'items.*.weight' => 'nullable|integer|min:1',
            'items.*.metadata' => 'nullable|array',
            'items.*.type_code' => 'required|string',
        ]);

        DB::transaction(function() use ($data, $rifa) {
            $map = [];
            foreach ($data['types'] ?? [] as $t) {
                $row = RewardTypes::updateOrCreate(
                    ['rifas_id' => $rifa, 'code' => $t['code']],
                    [
                        'name' => $t['name'],
                        'is_active' => (bool)($t['is_active'] ?? true),
                        'giro_por_usuario' => $t['giro_por_usuario'] ?? null,
                        'metadata' => $t['metadata'] ?? [],
                    ]
                );
                $map[$t['code']] = $row->id;
            }

            RewardItems::where('rifas_id', $rifa)->delete();

            foreach ($data['items'] ?? [] as $it) {
                $typeId = $map[$it['type_code']] ?? null;
                if (!$typeId) {
                    abort(422, "type_code inválido: '{$it['type_code']}'. Tipos aceitos: " . implode(',', array_keys($map)));
                }
                RewardItems::create([
                    'rifas_id'        => $rifa,
                    'reward_type_id'  => $typeId,
                    'text'            => $it['text'],
                    'type'            => $it['type'],
                    'status'          => $it['status'],
                    'position_number' => $it['position_number'] ?? null,
                    'min_tries_required' => $it['min_tries_required'] ?? null,
                    'guarantee_after' => $it['guarantee_after'] ?? null,
                    'weight'          => $it['weight'] ?? 1,
                    'metadata'        => $it['metadata'] ?? [],
                ]);
            }

        });

        return response()->json(['ok' => true]);
    }
}
