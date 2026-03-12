<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    use HasFactory;
        protected $fillable = [
        'user_id','rifas_id','reward_type_id','reward_item_id','outcome','consumed_passes','animation_payload','idempotency_key'
    ];
    protected $casts = ['animation_payload'=>'array'];

    public function rewardItem() {
        return $this->belongsTo(RewardItems::class, 'reward_item_id');
    }
}
