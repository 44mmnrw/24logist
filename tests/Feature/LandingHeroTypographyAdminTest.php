<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Models\LandingSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LandingHeroTypographyAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_and_save_hero_font_sizes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs(
            'landing/hero',
            UploadedFile::fake()->image('dashboard.png', 100, 100),
            'dashboard.png',
        );
        $this->actingAs(User::factory()->create());

        $section = LandingSection::query()->create([
            'slug' => 'hero',
            'name' => 'Главный экран',
            'title' => 'ЛогистРу',
            'seo_h1' => 'CRM для экспедиторов',
            'extra' => [
                'title_font_size' => 56,
                'subtitle_1_font_size' => 40,
                'subtitle_2_font_size' => 28,
                'carousel_delay_ms' => 5000,
                'carousel_slides' => [[
                    'image' => 'landing/hero/dashboard.png',
                    'alt' => 'Интерфейс',
                ]],
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $component = Livewire::test(EditLandingSection::class, ['record' => $section->getRouteKey()])
            ->set('data.hero_title_font_size', 64)
            ->set('data.hero_subtitle_1_font_size', 36)
            ->set('data.hero_subtitle_2_font_size', 22)
            ->assertSet('data.hero_title_font_size', 64)
            ->assertSet('data.hero_subtitle_1_font_size', 36)
            ->assertSet('data.hero_subtitle_2_font_size', 22);

        $formData = $component->instance()->form->getState();

        $this->assertEquals(64, $formData['hero_title_font_size']);
        $this->assertEquals(36, $formData['hero_subtitle_1_font_size']);
        $this->assertEquals(22, $formData['hero_subtitle_2_font_size']);

        $component
            ->call('save')
            ->assertHasNoFormErrors();

        $extra = $section->fresh()->extra;

        $this->assertSame(64, $extra['title_font_size']);
        $this->assertSame(36, $extra['subtitle_1_font_size']);
        $this->assertSame(22, $extra['subtitle_2_font_size']);
    }
}
