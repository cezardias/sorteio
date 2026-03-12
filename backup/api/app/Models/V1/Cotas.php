<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas};
use App\Services\FuncaoService;
class Cotas extends Model {
    use HasFactory;

    protected $table = 'cotas';

    protected $fillable = [
        'qntd_cota',
        'qntd_cota_min_order',
        'qntd_cota_max_order',
        'qntd_cota_max_client',
        'rifas_id',
    ];

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class);
    }

    public static function cotaCreateOrUpdate($qntdCota, $qntdCotaDigit, $qntdCotaMinOrder, $qntdCotaMaxOrder, $qntdCotaMaxClient, $rifaId) {
        $rifa = new FuncaoService();

        $qntdCota = $rifa->convertToDecimal($qntdCota);
        $qntdCotaDigit = $rifa->convertToDecimal($qntdCotaDigit);
        $qntdCotaMinOrder = $rifa->convertToDecimal($qntdCotaMinOrder);
        $qntdCotaMaxOrder = $rifa->convertToDecimal($qntdCotaMaxOrder);
        $qntdCotaMaxClient = $rifa->convertToDecimal($qntdCotaMaxClient);

        $result  = self::updateOrCreate(
            ['rifas_id' => $rifaId],
            [
                'qntd_cota' => $qntdCota,
                'qntd_cota_digit' => $qntdCotaDigit,
                'qntd_cota_min_order' => $qntdCotaMinOrder,
                'qntd_cota_max_order' => $qntdCotaMaxOrder,
                'qntd_cota_max_client' => $qntdCotaMaxClient,
                'rifas_id' => $rifaId,

            ]);

            return $result ? true : false;
    }

    public static function findQntdCota($rifaId) {
        return self::where('rifas_id', $rifaId)->first();
    }
}
