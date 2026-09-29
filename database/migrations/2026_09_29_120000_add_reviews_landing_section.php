<?php

use App\Services\LandingPageService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_sections')->where('slug', 'reviews')->exists()) {
            return;
        }

        $order = (int) (DB::table('landing_sections')->where('slug', 'pricing')->value('sort_order') ?? 7);
        DB::table('landing_sections')->where('sort_order', '>=', $order)->increment('sort_order');

        $now = now();
        DB::table('landing_sections')->insert([
            'slug' => 'reviews',
            'name' => 'Отзывы клиентов',
            'anchor' => 'reviews',
            'title' => 'Как логистРу помогает бизнесу в реальной работе',
            'description' => 'Отзывы экспедиторских компаний, перевозчиков и ИП о заявках, документах и ЭДО',
            'is_active' => false,
            'sort_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        app(LandingPageService::class)->clearCache();
    }

    public function down(): void
    {
        $order = DB::table('landing_sections')->where('slug', 'reviews')->value('sort_order');
        if ($order === null) {
            return;
        }

        DB::table('landing_blocks')->where('section_slug', 'reviews')->delete();
        DB::table('landing_sections')->where('slug', 'reviews')->delete();
        DB::table('landing_sections')->where('sort_order', '>', $order)->decrement('sort_order');

        app(LandingPageService::class)->clearCache();
    }
};
