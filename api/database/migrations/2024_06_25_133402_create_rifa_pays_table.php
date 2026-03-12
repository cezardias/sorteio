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
        Schema::create('rifa_pays', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(0)->comment('0 => pedente, 1 => aprovado, 2 => não aprovado');
            $table->tinyInteger('verify')->default(0)->comment('0 => verificacao de pagamento pendente, 1 => verificacao de pagamento feita');
            $table->decimal('value');
            $table->integer('qntd_number');
            $table->mediumText('pix_id')->nullable();
            $table->longText('qr_code')->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->string('cod')->unique();
            $table->string('checkout')->unique();
            $table->unsignedBigInteger('rifas_id');
            $table->foreign("rifas_id")->references("id")->on("rifas");
            $table->unsignedBigInteger('client_id');
            $table->foreign("client_id")->references("id")->on("clients");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifa_pays');
    }
};
