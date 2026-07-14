<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'epd_platform')->exists()) {
            return;
        }

        DB::table('landing_sections')->where('sort_order', '>=', 7)->increment('sort_order');

        $now = Carbon::now();

        DB::table('landing_sections')->insert([
            'slug' => 'epd_platform',
            'name' => 'Платформа ЭПД',
            'anchor' => 'epd-platform',
            'title' => 'Платформа ЭПД',
            'subtitle' => 'Обмен электронными перевозочными документами между участниками грузоперевозок',
            'badge_icon' => 'icon:epd-platform',
            'button_primary_text' => 'Подробнее о сервисе',
            'is_active' => true,
            'sort_order' => 7,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $packages = [
            ['100', '1 000 ₽', '10 ₽ / документ'],
            ['500', '3 000 ₽', '6 ₽ / документ'],
            ['1 000', '5 000 ₽', '5 ₽ / документ'],
            ['5 000', '20 000 ₽', '4 ₽ / документ'],
            ['10 000', '35 000 ₽', '3,5 ₽ / документ'],
            ['50 000', '150 000 ₽', '3 ₽ / документ'],
            ['100 000', '250 000 ₽', '2,5 ₽ / документ'],
        ];

        foreach ($packages as $index => [$documents, $price, $rate]) {
            DB::table('landing_blocks')->insert([
                'section_slug' => 'epd_platform',
                'block_type' => 'package',
                'title' => $documents,
                'subtitle' => 'документов',
                'price' => $price,
                'description' => $rate,
                'link' => '#quiz',
                'button_text' => 'Выбрать',
                'button_style' => 'ghost',
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }

    public function down(): void
    {
        if (! DB::table('landing_sections')->where('slug', 'epd_platform')->exists()) {
            return;
        }

        DB::table('landing_blocks')->where('section_slug', 'epd_platform')->delete();
        DB::table('landing_sections')->where('slug', 'epd_platform')->delete();
        DB::table('landing_sections')->where('sort_order', '>=', 8)->decrement('sort_order');

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
