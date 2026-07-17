<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('telegram_popup_mobile_url')->nullable()->after('telegram_popup_channel_url');
        });

        DB::table('site_settings')->update([
            'telegram_popup_channel_url' => 'https://telegram.me/logistru24',
            'telegram_popup_mobile_url' => 'tg://resolve?domain=logistru24',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('telegram_popup_mobile_url');
        });
    }
};
