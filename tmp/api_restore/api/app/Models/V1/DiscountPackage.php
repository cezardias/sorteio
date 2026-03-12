<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas};

class DiscountPackage extends Model
{
    use HasFactory;


    protected $fillable = [ 'qntd_cota', 'value_cota', 'valor_total', 'popular', 'status', 'cod_promo', 'rifas_id' ];

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class);
    }

    public static function createDiscountPackage($data) {
        $result = self::updateOrCreate(
            ['id' => $data->id],
            [
                'qntd_cota' => $data->qntd_cota,
                'value_cota' => $data->value_cota,
                'valor_total' => $data->valor_total,
                'popular' => $data->popular,
                'status' => $data->status,
                'cod_promo' => $data->cod_promo,
                'rifas_id' => $data->rifas_id,
            ]
        );

        return $result ? true : false;
    }


    public static function getAllPacotes($id) {
        return self::where('rifas_id', $id)->paginate(20);
    }
    public static function getOnePacote($id) {
        return self::where('id', $id)->first();
    }
}
