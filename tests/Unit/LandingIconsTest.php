<?php

namespace Tests\Unit;

use App\Filament\Forms\LandingIconSelect;
use App\Support\LandingIcons;
use App\Support\TablerIconTranslations;
use Tests\TestCase;

class LandingIconsTest extends TestCase
{
    public function test_it_normalizes_tabler_icons_without_changing_the_style(): void
    {
        $this->assertSame('tabler:truck', LandingIcons::normalize('tabler:truck'));
        $this->assertSame('tabler-filled:truck', LandingIcons::normalize('tabler-filled:truck'));
    }

    public function test_it_maps_a_saved_legacy_icon_to_tabler(): void
    {
        $this->assertSame('tabler:clipboard-list', LandingIcons::toTabler('icon:clipboard-list'));
        $this->assertSame('tabler:clipboard-list', LandingIcons::normalize('icon:clipboard-list'));
    }

    public function test_the_picker_search_returns_only_tabler_icons(): void
    {
        $options = LandingIcons::searchOptions('truck');

        $this->assertNotEmpty($options);
        $this->assertArrayHasKey('tabler:truck', $options);
        $this->assertArrayHasKey('tabler-filled:truck', $options);

        foreach (array_keys($options) as $value) {
            $this->assertTrue(
                str_starts_with($value, 'tabler:') || str_starts_with($value, 'tabler-filled:'),
                "Unexpected non-Tabler option [{$value}]",
            );
        }
    }

    public function test_frontend_href_points_to_the_local_tabler_sprite(): void
    {
        $href = LandingIcons::symbolHref('tabler:truck');

        $this->assertStringContainsString('/icons/tabler/v3.46.0/tabler-sprite.svg#tabler-truck', $href);
    }

    public function test_every_outline_icon_has_a_russian_title(): void
    {
        $sprite = file_get_contents(public_path('icons/tabler/v3.46.0/tabler-sprite.svg'));
        $translations = json_decode(
            file_get_contents(resource_path('data/tabler-icons-ru.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        preg_match_all('/<symbol id="tabler-([a-z0-9-]+)"/', $sprite, $matches);
        $iconNames = array_values(array_unique($matches[1]));

        $this->assertCount(count($iconNames), $translations);
        $this->assertSame([], array_values(array_diff($iconNames, array_keys($translations))));
        $this->assertNotContains('', array_map('trim', $translations));
    }

    public function test_the_picker_can_search_in_russian(): void
    {
        $truckOptions = LandingIcons::searchOptions('грузовик');
        $requestOptions = LandingIcons::searchOptions('заявки');
        $arrowOptions = LandingIcons::searchOptions('стрелка вправо');
        $telegramOptions = LandingIcons::searchOptions('телеграм');

        $this->assertArrayHasKey('tabler:truck', $truckOptions);
        $this->assertArrayHasKey('tabler-filled:truck', $truckOptions);
        $this->assertArrayHasKey('tabler:clipboard-list', $requestOptions);
        $this->assertArrayHasKey('tabler:arrow-right', $arrowOptions);
        $this->assertArrayHasKey('tabler:brand-telegram', $telegramOptions);
        $this->assertSame('Грузовик', TablerIconTranslations::title('truck'));
        $this->assertStringContainsString('Грузовик', LandingIcons::optionLabel('tabler:truck'));
        $this->assertStringContainsString('truck', LandingIcons::optionLabel('tabler:truck'));
    }

    public function test_the_public_picker_catalog_contains_every_icon_variant(): void
    {
        $catalog = json_decode(
            file_get_contents(public_path('icons/tabler/v3.46.0/tabler-icons-ru.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(5130, $catalog);
        $this->assertCount(1054, array_filter($catalog, fn (array $icon): bool => $icon['filled']));
        $catalogCategories = array_unique(array_merge(...array_column($catalog, 'categories')));
        $availableCategories = array_keys(LandingIconSelect::make('icon')->getCategories());

        sort($catalogCategories);
        sort($availableCategories);

        $this->assertSame(['Vehicles'], $catalog['truck']['categories']);
        $this->assertCount(41, $catalogCategories);
        $this->assertSame($catalogCategories, $availableCategories);
        $this->assertNotContains([], array_column($catalog, 'categories'));
        $this->assertSame('Грузовик', $catalog['truck']['title']);
        $this->assertStringContainsString('транспорт', $catalog['truck']['search']);
    }
}
