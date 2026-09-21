<?php

use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('landing_blocks')
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->whereIn('button_text', ['Запросить расчёт', 'Запросить расчет'])
            ->update([
                'button_text' => 'Получить предложение',
                'updated_at' => now(),
            ]);

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        DB::table('landing_blocks')
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->where('button_text', 'Получить предложение')
            ->update([
                'button_text' => 'Запросить расчёт',
                'updated_at' => now(),
            ]);

        app(LandingPageService::class)->clearCache();
    }
};
