<?php

namespace Tests\Unit;

use App\Support\LandingHeroCarouselForm;
use PHPUnit\Framework\TestCase;

class LandingHeroCarouselFormTest extends TestCase
{
    public function test_it_hydrates_default_hero_font_sizes_for_existing_sections(): void
    {
        $data = LandingHeroCarouselForm::hydrate([
            'slug' => 'hero',
            'extra' => [],
        ]);

        $this->assertSame(56, $data['extra']['title_font_size']);
        $this->assertSame(40, $data['extra']['subtitle_1_font_size']);
        $this->assertSame(28, $data['extra']['subtitle_2_font_size']);
    }

    public function test_it_limits_hero_font_sizes_saved_from_the_admin_form(): void
    {
        $data = LandingHeroCarouselForm::dehydrate([
            'slug' => 'hero',
            'extra' => [
                'title_font_size' => 120,
                'subtitle_1_font_size' => 8,
                'subtitle_2_font_size' => '',
            ],
        ]);

        $this->assertSame(96, $data['extra']['title_font_size']);
        $this->assertSame(12, $data['extra']['subtitle_1_font_size']);
        $this->assertSame(28, $data['extra']['subtitle_2_font_size']);
    }
}
