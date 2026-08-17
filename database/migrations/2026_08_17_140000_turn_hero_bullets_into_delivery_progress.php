<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('landing_blocks')
            ->where('section_slug', 'hero')
            ->where('block_type', 'bullet')
            ->update([
                'icon' => 'icon:doc-check-circle',
                'updated_at' => now(),
            ]);

        $this->clearCache();
    }

    public function down(): void
    {
        DB::table('landing_blocks')
            ->where('section_slug', 'hero')
            ->where('block_type', 'bullet')
            ->update([
                'icon' => 'icon:check-blue',
                'updated_at' => now(),
            ]);

        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
