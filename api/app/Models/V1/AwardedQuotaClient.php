<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\V1\Clients;
class AwardedQuotaClient extends Model
{
    use HasFactory;


    protected $fillable = [ 'awarded_number', 'price_awarded_quota', 'awarded_quotas_id', 'client_id', 'rifas_id' ];

    public function client(): HasOne {
        return $this->hasOne(Clients::class, 'id','client_id');
    }
}
