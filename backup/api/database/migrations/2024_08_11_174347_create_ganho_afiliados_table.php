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
        Schema::create('ganho_afiliados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('afiliado_id');
            $table->unsignedBigInteger('rifas_id');
            $table->unsignedBigInteger('rifa_pay_id');
            $table->integer('pedidos');
            $table->decimal('faturamento', 10, 2);
            $table->decimal('comissao', 10, 2);
            $table->tinyInteger('pago');
            $table->timestamps();
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->foreign('afiliado_id')->references('id')->on('afiliados')->onDelete('cascade');
            $table->foreign('rifa_pay_id')->references('id')->on('rifa_pays')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ganho_afiliados');
    }
};
