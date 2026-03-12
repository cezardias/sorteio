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
        Schema::create('reward_passes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('rifas_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reward_type_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('balance')->default(0);
            $t->string('source')->nullable();       // idempotência de grant
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();

            $t->unique(['user_id','rifas_id','reward_type_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_passes');
    }
};
