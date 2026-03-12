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
        Schema::create('reward_redemptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('rifas_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reward_type_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reward_item_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('outcome', ['reward','try_again','empty'])->default('try_again');
            $t->unsignedInteger('consumed_passes')->default(1);
            $t->json('animation_payload')->nullable(); // { angle, position }
            $t->string('idempotency_key')->nullable()->index();
            $t->timestamps();

            $t->index(['user_id','rifas_id','reward_type_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
