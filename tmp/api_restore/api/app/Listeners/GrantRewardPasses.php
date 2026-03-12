<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\RewardPass;
use App\Models\RewardTypes;
use Illuminate\Support\Facades\DB;

class GrantRewardPasses
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event) {
        $userId  = $event->user_id;
        $rifaId  = $event->rifas_id;
        $numbersBought = $event->numbers_count; // vindo do teu domínio de compra

        $types = RewardTypes::where('rifas_id',$rifaId)->where('is_active',true)->get();

        DB::transaction(function() use ($types, $numbersBought, $userId, $rifaId) {
            foreach ($types as $type) {
                $meta = json_decode($type->metadata ?? '{}', true);
                $per  = max(1, (int)($meta['numbers_per_pass'] ?? 0)); // se 0, não dá passe
                if ($per < 1) continue;

                $passes = intdiv($numbersBought, $per);
                if ($passes < 1) continue;

                // upsert de saldo
                $pass = RewardPass::firstOrCreate([
                    'user_id'        => $userId,
                    'rifas_id'       => $rifaId,
                    'reward_type_id' => $type->id,
                ], ['balance' => 0]);

                $pass->increment('balance', $passes);
            }
        });
    }
}
