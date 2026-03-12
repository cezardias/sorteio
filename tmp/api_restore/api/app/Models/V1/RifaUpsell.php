<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RifaUpsell extends Model
{
    use HasFactory;

    protected $fillable = [ 'qntd_cota', 'price_cota', 'price_total', 'qntd_min', 'qntd_max', 'localizacao', 'status', 'rifas_id' ];

    public static function createUpsell($id, $qntdCota, $priceCota, $priceTotal, $qntdMin, $qntdMax, $local, $status, $rifaId) {
        $result  = self::updateOrCreate(
            ['id' => $id],
            [
                'qntd_cota' => $qntdCota,
                'price_cota' => $priceCota,
                'price_total' => $priceTotal,
                'qntd_min' => $qntdMin,
                'qntd_max' => $qntdMax,
                'localizacao' => $local,
                'status' => $status,
                'rifas_id' => $rifaId,
            ]);

            return $result ? true : false;
    }

    public static function getAllUpsellRifa($id) {
        return self::where('rifas_id', $id)->get();

    }
}
