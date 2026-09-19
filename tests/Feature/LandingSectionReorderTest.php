<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\ListLandingSections;
use App\Models\LandingSection;
use App\Models\User;
use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class LandingSectionReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dragging_sections_saves_their_public_order_and_clears_the_landing_cache(): void
    {
        $sections = LandingSection::query()->orderBy('sort_order')->get();
        $reordered = $sections->reverse()->values();

        app(LandingPageService::class)->clearCache();
        app(LandingPageService::class)->sections();
        $this->assertTrue(Cache::has('landing.page.content.v3'));

        $this->actingAs(User::factory()->create());

        Livewire::test(ListLandingSections::class)
            ->assertSee('Изменить порядок')
            ->call('toggleTableReordering')
            ->assertSet('isTableReordering', true)
            ->assertSee('Готово')
            ->call('reorderTable', $reordered->modelKeys());

        $this->assertSame(
            $reordered->pluck('slug')->all(),
            LandingSection::query()->orderBy('sort_order')->pluck('slug')->all(),
        );
        $this->assertFalse(Cache::has('landing.page.content.v3'));
        $this->assertSame(
            $reordered->where('is_active', true)->pluck('slug')->all(),
            app(LandingPageService::class)->sections()->pluck('slug')->all(),
        );
    }
}
