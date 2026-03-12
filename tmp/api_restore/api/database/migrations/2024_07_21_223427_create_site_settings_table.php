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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_title');
            $table->string('logo_dark');
            $table->string('logo_light');
            $table->string('footer_company');
            $table->string('google_analytics')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('product_title');
            $table->string('product_subtitle')->nullable();
            $table->string('author')->nullable();
            $table->text('tags')->nullable();
            $table->string('share_title')->nullable();
            $table->string('share_image')->nullable();
            $table->text('share_description')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('whatsapp_group_url')->nullable();
            $table->string('instagram')->nullable();
            $table->string('helpdesk_url')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
