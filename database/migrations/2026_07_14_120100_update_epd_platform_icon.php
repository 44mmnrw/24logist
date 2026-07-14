<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('landing_sections')
            ->where('slug', 'epd_platform')
            ->update(['badge_icon' => 'icon:epd-platform']);

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }

    public function down(): void
    {
        DB::table('landing_sections')
            ->where('slug', 'epd_platform')
            ->update(['badge_icon' => 'icon:document-signed']);

        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
