<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas};
use App\Services\FuncaoService;
class RifasAwarded extends Model {
    protected $fillable = [
        'cotas_double',
        'text_cotas_double',
        'title_cotas_awarded',
        'description_cotas_awarded',
        'title_upsell',
        'description_upsell',
        'rifas_id',
    ];

    use HasFactory;

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class);
    }

    public static function rifasAwardedCreateOrUpdate($cotasDouble, $textVotasDouble, $titleCotasAwarded, $descriptionCotasAwarded, $titleUpsell, $descriptionUpsell, $rifaId) {

        $result  = self::updateOrCreate(
            ['rifas_id' => $rifaId],
            [
                'cotas_double' => $cotasDouble,
                'text_cotas_double' => $textVotasDouble,
                'title_cotas_awarded' => $titleCotasAwarded,
                'description_cotas_awarded' => $descriptionCotasAwarded,
                'title_upsell' => $titleUpsell,
                'description_upsell' => $descriptionUpsell,
                'rifas_id' => $rifaId,

            ]);

            return $result ? true : false;

    }
}
