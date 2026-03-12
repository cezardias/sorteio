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
        Schema::create('cotas', function (Blueprint $table) {
            $table->id();
            $table->integer('qntd_cota');
            $table->integer('qntd_cota_min_order');
            $table->integer('qntd_cota_max_order');
            $table->integer('qntd_cota_max_client');

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
        Schema::dropIfExists('cotas');
    }
};
