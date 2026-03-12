<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RifaImage extends Model
{
    use HasFactory;

    protected $fillable = [ 'path', 'rifas_id' ];

    public static function createImage($img,  $rifaId) {
        $result  = self::updateOrCreate(
            [
                'path' => $img,
                'rifas_id' => $rifaId,

            ]);

            return $result ? true : false;
    }

    public static function getRifaImagens($id) {
        return self::where('rifas_id', $id)->get();
    }
    public static function getOneRifaImagens($id) {
        return self::where('id', $id)->first();
    }
}
