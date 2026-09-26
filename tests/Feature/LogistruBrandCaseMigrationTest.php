<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LogistruBrandCaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_site_copy_and_json_content_use_lowercase_brand(): void
    {
        $settings = SiteSetting::instance();
        DB::table('site_settings')->where('id', $settings->id)->update([
            'org_brand_name' => 'ЛогистРу',
            'telegram_popup_description' => 'Новости ЛогистРу',
        ]);
        DB::table('cms_pages')->insert([
            'slug' => 'brand-case-test',
            'title' => 'О ЛогистРу',
            'body' => '<p>ЛогистРу и логистРу</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('landing_sections')->insert([
            'slug' => 'brand-case-test',
            'name' => 'ЛогистРу',
            'extra' => json_encode(['caption' => 'Сервис ЛогистРу']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_09_26_020000_normalize_logistru_brand_case.php');
        $migration->up();

        $this->assertDatabaseHas('site_settings', [
            'id' => $settings->id,
            'org_brand_name' => 'логистРу',
            'telegram_popup_description' => 'Новости логистРу',
        ]);
        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'brand-case-test',
            'title' => 'О логистРу',
            'body' => '<p>логистРу и логистРу</p>',
        ]);

        $section = DB::table('landing_sections')->where('slug', 'brand-case-test')->first();
        $this->assertSame('логистРу', $section->name);
        $this->assertSame('Сервис логистРу', json_decode($section->extra, true)['caption']);
    }
}
