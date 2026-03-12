<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ClientPassToken
{
    public static function make(int $clientId, int $rifaId, int $ttlMinutes = 60*24*180): string
    {
        $payload = [
            'client_id' => $clientId,
            'rifa_id'   => $rifaId,
            'exp'       => now()->addMinutes($ttlMinutes)->timestamp,
            'nonce'     => (string) Str::uuid(),
        ];
        return Crypt::encryptString(json_encode($payload));
    }

    public static function parse(?string $token): ?array
    {
        if (!$token) return null;
        try {
            $json = Crypt::decryptString($token);
            $data = json_decode($json, true);
            if (!is_array($data)) return null;
            if (($data['exp'] ?? 0) < time()) return null;
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
