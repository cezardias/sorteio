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
        Schema::create('rifas', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->string("slug");
            $table->string("description_resume");
            $table->enum('show_site', ['sim', 'nao']);
            $table->enum('emphasis', ['sim', 'nao']);
            $table->string("show_top");
            $table->string("video");
            $table->string("status");
            $table->decimal('price', 10, 2)->nullable();
            $table->text("description_sortition");
            $table->text("description_product");
            $table->text("description_role");
            $table->text("description_order_approve");
            $table->dateTime('data_sortition')->nullable();
            $table->dateTime('initial_sale')->nullable();
            $table->dateTime('end_sale')->nullable();
            $table->dateTime('end_rifa')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign("user_id")->references("id")->on("users");


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rifas');
    }
};
