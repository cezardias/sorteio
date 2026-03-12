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
        Schema::create('rifa_upsells', function (Blueprint $table) {
            $table->id();
            $table->integer('qntd_cota');
            $table->decimal('price_cota');
            $table->decimal('price_total');
            $table->integer('qntd_min');
            $table->integer('qntd_max');
            $table->string('localizacao');
            $table->string('status');

            $table->unsignedBigInteger('rifas_id');
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifa_upsells');
    }
};
