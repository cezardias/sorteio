<?php

namespace App\Models;

use App\Models\V1\Rifas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardPassGrant extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'rifas_id',
        'source',
        'passes_total',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function rifa()
    {
        return $this->belongsTo(Rifas::class, 'rifas_id');
    }
}
