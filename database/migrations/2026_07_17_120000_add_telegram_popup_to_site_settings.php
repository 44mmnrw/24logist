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
            $table->boolean('telegram_popup_enabled')->default(false);
            $table->string('telegram_popup_badge', 100)->default('Telegram-канал');
            $table->string('telegram_popup_title')->default('Будьте в курсе обновлений');
            $table->text('telegram_popup_description')->nullable();
            $table->string('telegram_popup_button_text', 100)->default('Подписаться на канал');
            $table->string('telegram_popup_dismiss_text', 100)->default('Не сейчас');
            $table->text('telegram_popup_channel_url')->nullable();
            $table->unsignedInteger('telegram_popup_show_delay')->default(5);
            $table->unsignedInteger('telegram_popup_auto_close_delay')->default(15);
        });

        DB::table('site_settings')->update([
            'telegram_popup_description' => 'В канале ЛогистРу — новости об изменениях в транспортном ЭДО, обновления сервиса и полезные материалы для экспедиторов.',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'telegram_popup_enabled',
                'telegram_popup_badge',
                'telegram_popup_title',
                'telegram_popup_description',
                'telegram_popup_button_text',
                'telegram_popup_dismiss_text',
                'telegram_popup_channel_url',
                'telegram_popup_show_delay',
                'telegram_popup_auto_close_delay',
            ]);
        });
    }
};
