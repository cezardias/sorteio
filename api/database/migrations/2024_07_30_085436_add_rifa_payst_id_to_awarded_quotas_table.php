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
        Schema::table('awarded_quotas', function (Blueprint $table) {
            $table->unsignedBigInteger('rifa_pays_id')->nullable()->after('client_id');
            $table->foreign('rifa_pays_id')->references('id')->on('rifa_pays')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('awarded_quotas', function (Blueprint $table) {
            $table->dropForeign(['rifa_pays_id']);
            $table->dropColumn('rifa_pays_id');
        });
    }
};
