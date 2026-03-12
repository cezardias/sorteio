<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\V1\Clients;
use Illuminate\Support\Str;

class Afiliado extends Model
{
    use HasFactory;

    protected $fillable = [
        'cellphone',
        'link',
        'porcent',
        'type',
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Clients::class);
    }

    public function ganhoAfiliado()
    {
        // Correção para refletir que um Afiliado pode ter muitos GanhoAfiliado
        return $this->hasMany(GanhoAfiliado::class, 'afiliado_id');
    }
    public function ganhoAfiliadoOne()
    {
        // Correção para refletir que um Afiliado pode ter muitos GanhoAfiliado
        return $this->hasOne(GanhoAfiliado::class, 'afiliado_id');
    }

    public static function findAfiliado($cellphone)
    {
        return self::with(['ganhoAfiliado', 'client'])
            ->where('cellphone', $cellphone)
            ->first();
    }

    public static function findAllAfiliado()
    {
        return self::with(['ganhoAfiliado', 'client'])->get();
    }

    public static function findOneAfiliadoById($id)
    {
        return self::with(['ganhoAfiliado', 'client'])
            ->where('id', $id)
            ->first();
    }

    public static function createAfiliado($data, $client)
    {
        $link = self::createLinkAfiliado($data, $client);

        return self::create([
            'cellphone' => $data->cellphone,
            'link' => $link,
            'porcent' => $data->porcent,
            'type' => $data->type,
            'client_id' => $client->id,
        ]);
    }

    private static function createLinkAfiliado($data, $client) {
        do {
            $code = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', ceil(9 / 62))), 1,9);
        } while (self::where('link', $code)->exists());
        $code = Str::slug($client->name).'-'.Str::slug($client->surname).'-'.$code;

        return $code;
    }
}
