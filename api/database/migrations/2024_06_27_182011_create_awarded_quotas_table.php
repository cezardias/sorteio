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
        Schema::create('awarded_quotas', function (Blueprint $table) {
            $table->id();
            $table->integer('number_cota')->index()->nullable();
            $table->string('award')->nullable();
            $table->string('show_site')->nullable();
            $table->string('status')->index()->nullable();
            $table->unsignedBigInteger('rifas_id')->nullable();
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign("client_id")->references("id")->on("clients");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awarded_quotas');
    }
};
