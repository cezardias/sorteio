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
        Schema::create('reward_item_stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reward_item_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('total')->default(0);
            $t->unsignedInteger('remaining')->default(0);
            $t->timestamps();

            $t->unique('reward_item_id');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_item_stocks');
    }
};
