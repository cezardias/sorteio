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
        Schema::create('awarded_quota_clients', function (Blueprint $table) {
            $table->id();
            $table->integer('awarded_number');
            $table->decimal('price_awarded_quota');
            $table->unsignedBigInteger('awarded_quotas_id');
            $table->foreign("awarded_quotas_id")->references("id")->on("awarded_quotas");
            $table->unsignedBigInteger('client_id');
            $table->foreign("client_id")->references("id")->on("clients");
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
        Schema::dropIfExists('awarded_quota_clients');
    }
};
