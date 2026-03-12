<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas};

use App\Services\FuncaoService;
class RifasPayment extends Model {
    use HasFactory;

    protected $fillable = [
        'time_pay',
        'gateway',
        'name',
        'service_charge',
        'text_service_charge',
        'rifas_id',
    ];

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class);
    }

    public static function rifasPaymentCreateOrUpdate($timePay, $gateway, $serviceCharge, $textServiceCharge, $rifaId) {
        $rifa = new FuncaoService();

        $timePay = $rifa->convertToDecimal($timePay);
        $serviceCharge = $rifa->convertToDecimal($serviceCharge);

        $result  = self::updateOrCreate(
            ['rifas_id' => $rifaId],
            [
                'time_pay' => $timePay,
                'gateway' => $gateway,
                'service_charge' => $serviceCharge,
                'text_service_charge' => $textServiceCharge,
                'rifas_id' => $rifaId,

            ]);

            return $result ? true : false;
    }
}
