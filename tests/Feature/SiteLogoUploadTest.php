<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditGeneralSiteSetting;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Support\StructuredData;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uploads_a_persistent_site_logo_and_public_pages_use_it(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $settings = SiteSetting::instance();
        $this->assertSame(asset('images/logo.svg'), app(SiteSettingsService::class)->logoUrl());

        Livewire::test(EditGeneralSiteSetting::class)
            ->assertSee('Основной логотип сайта')
            ->set('data.site_logo_path', UploadedFile::fake()->image('new-logo.png', 264, 64))
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $settings->fresh()->site_logo_path;
        $this->assertIsString($path);
        $this->assertStringStartsWith('site/logo/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame('/storage/'.$path, app(SiteSettingsService::class)->logoUrl());

        $html = view('components.landing.logo')->render();
        $this->assertStringContainsString('src="/storage/'.$path.'"', $html);
        $this->get('/')->assertOk()->assertSee('/storage/'.$path, false);
        $this->assertSame('/storage/'.$path, Filament::getPanel('admin')->getBrandLogo());
        $this->assertSame(url('/storage/'.$path), StructuredData::organization()['logo']['url']);
    }

    public function test_saved_logo_survives_other_settings_changes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('site/logo/brand.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $settings = SiteSetting::instance();
        $settings->update(['site_logo_path' => 'site/logo/brand.svg']);
        $this->actingAs(User::factory()->create());

        Livewire::test(EditGeneralSiteSetting::class)
            ->set('data.blog_title', 'Обновлённый блог')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('site/logo/brand.svg', $settings->fresh()->site_logo_path);
        Storage::disk('public')->assertExists('site/logo/brand.svg');
    }
}
