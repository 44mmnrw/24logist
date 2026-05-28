<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'driver_cabinet')->exists()) {
            return;
        }

        DB::table('landing_sections')
            ->where('sort_order', '>=', 8)
            ->increment('sort_order');

        $now = Carbon::now();

        DB::table('landing_sections')->insert([
            'slug' => 'driver_cabinet',
            'name' => 'Личный кабинет водителя',
            'title' => 'Личный кабинет водителя',
            'description' => 'Водитель видит рейсы, статусы и ключевые документы в одном окне. Всё под рукой без лишних звонков и чатов.',
            'badge_text' => 'Для водителя',
            'badge_icon' => 'icon:user-driver',
            'extra' => json_encode([
                'pill_left_text' => 'Ссылка в один клик',
                'pill_left_icon' => 'icon:link',
                'pill_right_text' => 'Статусы в реальном времени',
                'pill_right_icon' => 'icon:clock',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_active' => true,
            'sort_order' => 8,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sectionSlug = 'driver_cabinet';
        $bullets = [
            'Просмотр назначенных рейсов и адресов загрузки/выгрузки',
            'Подтверждение этапов перевозки прямо с телефона',
            'Доступ к документам по рейсу без установки приложения',
        ];

        foreach ($bullets as $index => $title) {
            DB::table('landing_blocks')->insert([
                'section_slug' => $sectionSlug,
                'block_type' => 'bullet',
                'title' => $title,
                'icon' => 'icon:check-blue',
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! DB::table('landing_sections')->where('slug', 'driver_cabinet')->exists()) {
            return;
        }

        DB::table('landing_blocks')
            ->where('section_slug', 'driver_cabinet')
            ->delete();

        DB::table('landing_sections')
            ->where('slug', 'driver_cabinet')
            ->delete();

        DB::table('landing_sections')
            ->where('sort_order', '>=', 9)
            ->decrement('sort_order');
    }
};
