<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('landing_sections')->where('slug', 'growth')->first();

        if (! $section) {
            return;
        }

        $extra = json_decode($section->extra ?? '[]', true) ?: [];

        if (! isset($extra['customer_names'])) {
            $extra['customer_names'] = [
                ['name' => 'ООО "ГК «ЛОГОС»"'],
                ['name' => 'АО "УКЗ"'],
                ['name' => 'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"'],
                ['name' => 'ООО "МЕТАЛЛИНВЕСТСПБ"'],
                ['name' => 'ООО "КЛИМАТ-КОМПЛЕКС"'],
            ];

            DB::table('landing_sections')->where('slug', 'growth')->update([
                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }

    public function down(): void
    {
        $section = DB::table('landing_sections')->where('slug', 'growth')->first();

        if (! $section) {
            return;
        }

        $extra = json_decode($section->extra ?? '[]', true) ?: [];
        unset($extra['customer_names']);

        DB::table('landing_sections')->where('slug', 'growth')->update([
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
