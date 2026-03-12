<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'cpf')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('cpf')->nullable()->after('cellphone');
            });
        }

        if (!Schema::hasColumn('clients', 'email')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('email')->nullable()->after('cpf');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'email']);
        });
    }
};
