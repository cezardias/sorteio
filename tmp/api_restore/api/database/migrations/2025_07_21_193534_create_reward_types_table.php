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
        Schema::create('reward_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('rifas_id')->constrained()->cascadeOnDelete();
            $t->string('code'); // 'wheel' | 'chest'
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('giro_por_usuario')->nullable(); // limite por usuário (opcional)
            $t->json('metadata')->nullable(); // {"numbers_per_pass": 10}
            $t->timestamps();

            $t->unique(['rifas_id','code']); // 1 registro por tipo por rifa
            $t->index(['rifas_id','is_active']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_types');
    }
};
