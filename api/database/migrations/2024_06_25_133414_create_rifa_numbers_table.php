<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rifa_numbers', function (Blueprint $table) {
            $table->id();
            $table->longText('numbers')->nullable();
            $table->tinyInteger('status')->index()->default(0)->comment('0 => pagamento pedente, 1 => pago, 2 => pagamento não aprovado');

            $table->unsignedBigInteger('rifas_id');
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->unsignedBigInteger('pay_id');
            $table->foreign("pay_id")->references("id")->on("rifa_pays");
            $table->unsignedBigInteger('client_id');
            $table->foreign("client_id")->references("id")->on("clients");
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifa_numbers');
    }
};
