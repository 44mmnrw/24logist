<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('community_telegram_client_id')->nullable()->after('community_max_enabled');
            $table->text('community_telegram_client_secret')->nullable()->after('community_telegram_client_id');
            $table->text('community_telegram_bot_token')->nullable()->after('community_telegram_client_secret');
            $table->text('community_telegram_redirect_uri')->nullable()->after('community_telegram_bot_token');
            $table->text('community_max_bot_username')->nullable()->after('community_telegram_redirect_uri');
            $table->text('community_max_bot_token')->nullable()->after('community_max_bot_username');
            $table->text('community_max_webhook_secret')->nullable()->after('community_max_bot_token');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'community_telegram_client_id',
                'community_telegram_client_secret',
                'community_telegram_bot_token',
                'community_telegram_redirect_uri',
                'community_max_bot_username',
                'community_max_bot_token',
                'community_max_webhook_secret',
            ]);
        });
    }
};
