<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas};
class RifasOthers extends Model {

    protected $fillable = [
        'facebook_pixel',
        'facebook_token',
        'tiktok_pixel',
        'whatsapp_group',
        'link_ebook',
        'nota_fiscal',
        'rifas_id',
    ];

    use HasFactory;


    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class);
    }

    public static function rifasOthersCreateOrUpdate($facebook_pixel, $facebook_token, $tiktok_pixel, $whatsapp_group, $link_ebook, $nota_fiscal, $rifaId) {
        $result = self::updateOrCreate(
            ['rifas_id' => $rifaId],
            [
                'facebook_pixel' => $facebook_pixel,
                'facebook_token' => $facebook_token,
                'tiktok_pixel' => $tiktok_pixel,
                'whatsapp_group' => $whatsapp_group,
                'link_ebook' => $link_ebook,
                'nota_fiscal' => $nota_fiscal,
                'rifas_id' => $rifaId,
            ]
        );

        return $result ? true : false;
    }


}
