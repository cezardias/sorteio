<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\V1\{Rifas, Clients};

class RifaWinner extends Model
{
    use HasFactory;

    protected $fillable = [
        'img',
        'name',
        'ticket',
        'rifas_id',
        'client_id',
    ];

    public function rifa(): BelongsTo {
        return $this->belongsTo(Rifas::class, 'rifas_id', 'id');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Clients::class);
    }

    public static function getAllWinners() {
        return self::with(['rifa', 'client'])->latest()->get() ?? false;
    }
    public static function findWinner($id) {
        return self::with(['rifa', 'client'])->where('id', $id)->first() ?? false;
    }

    public static function defineWinner($data, $client, $img) {
        return self::create([
            'img' => $img,
            'ticket' => $data->ticket,
            'rifas_id' => $data->rifas_id,
            'client_id' => $client,
        ]);
    }
    public static function editarWinner($data, $client, $img) {
        return self::where('id', $data->id)->update([
            'img' => $img,
            'ticket' => $data->ticket,
            'rifas_id' => $data->rifas_id,
            'client_id' => $client,
        ]);
    }
}
