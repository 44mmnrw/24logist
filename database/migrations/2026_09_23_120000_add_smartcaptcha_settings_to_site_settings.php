<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('smartcaptcha_commercial_offer_enabled')->default(false);
            $table->boolean('smartcaptcha_contact_enabled')->default(false);
            $table->string('smartcaptcha_site_key')->nullable();
            $table->text('smartcaptcha_server_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['smartcaptcha_commercial_offer_enabled', 'smartcaptcha_contact_enabled', 'smartcaptcha_site_key', 'smartcaptcha_server_key']);
        });
    }
};
