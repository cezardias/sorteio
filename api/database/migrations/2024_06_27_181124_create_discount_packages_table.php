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
        Schema::create('discount_packages', function (Blueprint $table) {
            $table->id();
            $table->integer('qntd_cota')->nullable();
            $table->decimal('value_cota')->nullable();
            $table->decimal('valor_total')->nullable();
            $table->string('popular')->nullable();
            $table->string('status')->nullable();
            $table->string('cod_promo')->nullable();
            $table->unsignedBigInteger('rifas_id')->nullable();
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_packages');
    }
};
