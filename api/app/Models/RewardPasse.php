<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardPasse extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','rifas_id','reward_type_id','balance','source','expires_at'];
    protected $casts = ['expires_at'=>'datetime'];
}
