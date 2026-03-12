<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\V1\RifaPay;
use App\Models\V1\RifaNumber;
use App\Models\V1\Rifas;
use Tests\TestCase;

class RifaNumberTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_duplicate_numbers_when_two_users_buy_at_the_same_time()
    {
        // Configuração inicial
        // $rifa = Rifas::find(11);

        $data = [
            'rifas_id' => 11,
            'client_id' => 1,
            'qntd_number' => 5
        ];

        // Simula duas requisições concorrentes
        $response1 = null;
        $response2 = null;

        DB::transaction(function () use ($data, &$response1) {
            $rifaPay = RifaPay::create($data);
            $response1 = RifaNumber::applyRifa((object) $data, $rifaPay);
        });

        DB::transaction(function () use ($data, &$response2) {
            $rifaPay = RifaPay::create($data);
            $response2 = RifaNumber::applyRifa((object) $data, $rifaPay);
        });

        // Verifica se as respostas são diferentes
        $this->assertNotEquals($response1[1], $response2[1], "Os números gerados para ambas as compras devem ser diferentes.");
    }
}
