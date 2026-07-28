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
            $table->unsignedTinyInteger('telegram_popup_scroll_percent')
                ->default(55)
                ->after('telegram_popup_show_delay');
        });

        DB::table('site_settings')
            ->where('telegram_popup_show_delay', '<', 45)
            ->update(['telegram_popup_show_delay' => 45]);

        DB::table('site_settings')->update([
            'telegram_popup_auto_close_delay' => 0,
            'telegram_popup_scroll_percent' => 55,
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('telegram_popup_scroll_percent');
        });
    }
};
