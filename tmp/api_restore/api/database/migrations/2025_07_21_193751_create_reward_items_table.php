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
        Schema::create('reward_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rifas_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reward_type_id')->constrained()->cascadeOnDelete();
            $t->string('text');
            $t->enum('type', ['reward','try_again'])->default('reward');
            $t->enum('status', ['active','blocked','immediate'])->default('active');
            $t->unsignedInteger('position_number')->nullable();  // para roleta
            $t->unsignedInteger('min_tries_required')->nullable();
            $t->unsignedInteger('guarantee_after')->nullable();  // “pity” por item (opcional)
            $t->unsignedInteger('weight')->default(1);
            $t->json('metadata')->nullable();
            $t->timestamps();

            $t->index(['rifas_id','reward_type_id','status']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_items');
    }
};
