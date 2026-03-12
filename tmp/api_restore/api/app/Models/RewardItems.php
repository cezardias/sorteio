<?php

namespace App\Models;

use App\Models\V1\Rifas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardItems extends Model
{
    use HasFactory;

     protected $fillable = [
        'text','type','status','position_number','min_tries_required',
        'guarantee_after','weight','metadata','rifas_id','reward_type_id'
    ];
    protected $casts = ['metadata'=>'array'];

    public function rewardType() {
        return $this->belongsTo(RewardTypes::class, 'reward_type_id');
    }

    public function rifas() {
        return $this->belongsTo(Rifas::class, 'rifas_id');
    }
}
