<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('landing_blocks')->where('section_slug', 'header')->where('block_type', 'header_button')->exists()) {
            return;
        }

        $section = DB::table('landing_sections')->where('slug', 'header')->first();

        if (! $section) {
            return;
        }

        $extra = json_decode($section->extra ?? '{}', true);
        if (! is_array($extra)) {
            $extra = [];
        }

        $demoText = trim((string) ($extra['demo_button_text'] ?? 'Получить демо'));
        $now = now();

        $buttons = [
            [
                'section_slug' => 'header',
                'block_type' => 'header_button',
                'title' => 'Войти',
                'link' => '/admin/login',
                'button_style' => 'link',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_slug' => 'header',
                'block_type' => 'header_button',
                'title' => $demoText !== '' ? $demoText : 'Получить демо',
                'link' => '#quiz',
                'button_style' => 'primary',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('landing_blocks')->insert($buttons);

        unset($extra['demo_button_text']);

        DB::table('landing_sections')
            ->where('slug', 'header')
            ->update([
                'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('landing_blocks')
            ->where('section_slug', 'header')
            ->where('block_type', 'header_button')
            ->delete();
    }
};
