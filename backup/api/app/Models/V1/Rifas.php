<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasOne, hasMany};
use Carbon\Carbon;
use App\Services\FuncaoService;
use App\Models\V1\{RifasAwarded, RifasOthers, RifasPayment, AwardedQuota, AwardedQuotaClient, RifaPay, DiscountPackage};

class Rifas extends Model
{

    protected $fillable = [
        'title',
        'slug',
        'description_resume',
        'show_site',
        'emphasis',
        'show_top',
        'video',
        'img',
        'status',
        'price',
        'description_sortition',
        'description_product',
        'description_role',
        'description_order_approve',
        'data_sortition',
        'initial_sale',
        'end_sale',
        'user_id',
        'winner_id',
    ];


    use HasFactory;

    public function cota(): HasOne {
        return $this->hasOne(Cotas::class);
    }

    public function rifaAwarded(): HasOne {
        return $this->hasOne(RifasAwarded::class);
    }
    public function rifaOthers(): HasOne {
        return $this->hasOne(RifasOthers::class);
    }
    public function rifaPayment(): HasOne {
        return $this->hasOne(RifasPayment::class);
    }
    public function rifaPay(): hasMany {
        return $this->hasMany(RifaPay::class);
    }
    public function rifaPayActiva(): hasMany {
        return $this->hasMany(RifaPay::class)->where('status', 1);
    }
    public function rifaNumberActive(): hasMany {
        return $this->hasMany(RifaNumber::class)->where('status', 1);
    }
    public function rifaNumberReservado(): hasMany {
        return $this->hasMany(RifaNumber::class)->where('status', 0);
    }
    public function rifaPayToday()
    {
        return $this->hasMany(RifaPay::class, 'rifas_id')
            ->where('status', 1)
            ->whereDate('created_at', Carbon::today());
    }

    public function AwardedQuota(): hasMany {
        return $this->hasMany(AwardedQuota::class);
    }
    public function AwardedQuotaClient(): hasMany {
        return $this->hasMany(AwardedQuotaClient::class);
    }
    public function rifaImage(): hasMany {
        return $this->hasMany(RifaImage::class);
    }

    public function discountPackage(): hasMany {
        return $this->hasMany(DiscountPackage::class);
    }

    public static function rifaCreateOrUpdate($title, $slug, $description_resume, $show_site, $emphasis, $show_top, $video, $status, $price, $description_sortition, $description_product, $description_role, $description_order_approve, $data_sortition, $initial_sale, $end_sale, $end_rifa, $user_id, $rifa_id) {
        $rifaService = new FuncaoService();
        $price = $rifaService->convertToDecimal($price);
        $result  = self::updateOrCreate(
            ['id' => $rifa_id],
            [
                'title' => $title,
                'slug' => $slug,
                'description_resume' => $description_resume,
                'show_site' => $show_site,
                'emphasis' => $emphasis,
                'show_top' => $show_top,
                'video' => $video,
                'status' => $status,
                'price' => $price,
                'description_sortition' => $description_sortition,
                'description_product' => $description_product,
                'description_role' => $description_role,
                'description_order_approve' => $description_order_approve,
                'data_sortition' => $data_sortition,
                'initial_sale' => $initial_sale,
                'end_sale' => $end_sale,
                'end_rifa' => $end_rifa,
                'user_id' => $user_id,
            ]);
            $is_created = $result->wasRecentlyCreated || $result->wasChanged();


            $rifa_id = $result->id;

            return $result->id ?? false;
    }

    public static function findRifa($id) {
        return self::with(['cota', 'rifaImage'])->where('id',$id)->first();
    }

    public static function getAllRifasActivas() {
        return self::with(['cota', 'rifaAwarded', 'rifaOthers', 'rifaPayment', 'AwardedQuota.client', 'rifaImage', 'AwardedQuotaClient'])->where('status','ativas')->latest()->get();
    }
    public static function getAllRifas() {
        return self::with(['cota', 'rifaAwarded', 'rifaOthers', 'rifaPayment', 'AwardedQuota.client', 'rifaImage', 'rifaPay', 'AwardedQuotaClient'])->where('show_site', 'sim')->latest()->get();
    }
    public static function getOneRifa($id) {
        return self::with(['cota', 'rifaAwarded', 'rifaOthers', 'rifaPayment', 'AwardedQuota.client', 'discountPackage', 'rifaImage', 'AwardedQuotaClient'])->where("id", $id)->first();
    }
    public static function getOneRifaActiva($id) {
        return self::with(['cota', 'rifaAwarded', 'rifaOthers', 'rifaPayment', 'AwardedQuota.client', 'rifaImage', 'AwardedQuotaClient'])->where("id", $id)->where('status','ativas')->first();
    }
    public static function getOneRifaEdit($id) {
        return self::with(['cota', 'rifaAwarded', 'rifaOthers', 'rifaPayment', 'AwardedQuota.client', 'rifaImage', 'AwardedQuotaClient'])->where("id", $id)->where('status','ativas')->first();
    }
    public static function RifaActiva() {
        return self::where('status','ativas')->first();
    }

    public static function updateStatusToActive($id) {
        $rifa =  self::where("id", $id)->first();
        $initialSaleDate =  Carbon::parse($rifa->initial_sale)->toDateString(); // Formatar a data para comparar apenas a data
        $todayDate = Carbon::today()->toDateString(); // Data de hoje no formato correto
        return [$initialSaleDate === $todayDate, $initialSaleDate , $todayDate];

        if ($initialSaleDate === $todayDate) {
            $rifa->status = 'ativas';
            return $rifa->save();
        }
        // return $rifa->save(); // Salvar as alterações feitas no status e retornar o resultado da operação
    }



}
