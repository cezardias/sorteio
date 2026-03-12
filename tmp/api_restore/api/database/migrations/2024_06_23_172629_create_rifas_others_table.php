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
        Schema::create('rifas_others', function (Blueprint $table) {
            $table->id();
            $table->json(column: 'facebook_pixel')->nullable();
            $table->string('facebook_token')->nullable();
            $table->json('tiktok_pixel')->nullable();

            $table->string('whatsapp_group')->nullable();
            $table->string('link_ebook')->nullable();
            $table->string('nota_fiscal')->nullable();

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
        Schema::dropIfExists('rifas_others');
    }
};
