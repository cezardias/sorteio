<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentInfo extends Model
{
    use HasFactory;

    protected $table = 'payment_info';
    protected $fillable = [
        'name',
        'gateway',
        'token',
        'public_key',
        'api_client_id',
        'billing_name',
    ];
}
