<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardItemStock extends Model
{
    use HasFactory;
    protected $fillable = ['reward_item_id','total','remaining'];

    public function item() {
        return $this->belongsTo(RewardItems::class,'reward_item_id');
    }
}
