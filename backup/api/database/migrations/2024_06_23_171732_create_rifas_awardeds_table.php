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
        Schema::create('rifas_awardeds', function (Blueprint $table) {
            $table->id();
            $table->string('cotas_double')->nullable();
            $table->string('text_cotas_double')->nullable();
            $table->string('title_cotas_awarded')->nullable();
            $table->string('description_cotas_awarded')->nullable();
            $table->string('title_upsell')->nullable();
            $table->string('description_upsell')->nullable();

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
        Schema::dropIfExists('rifas_awardeds');
    }
};
