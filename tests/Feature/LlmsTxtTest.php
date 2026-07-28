<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LlmsTxtTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_text_is_the_complete_llms_txt_response(): void
    {
        $content = <<<'MD'
# Пользовательский llms.txt

Только явно заданное содержимое.

- [Главная](https://24logist.ru/)
MD;

        SiteSetting::instance()->update([
            'org_brand_name' => 'Не должно попасть в ответ',
            'org_email' => 'hidden@example.com',
            'llms_txt_extra' => $content,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $response = $this->get(route('seo.llms'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertSame($content."\n", $response->getContent());
        $this->assertStringNotContainsString('Не должно попасть в ответ', $response->getContent());
        $this->assertStringNotContainsString('hidden@example.com', $response->getContent());
        $this->assertStringNotContainsString('## Дополнительно', $response->getContent());
    }

    public function test_empty_setting_produces_an_empty_llms_txt_response(): void
    {
        SiteSetting::instance()->update(['llms_txt_extra' => null]);
        app(SiteSettingsService::class)->clearCache();

        $this->get(route('seo.llms'))
            ->assertOk()
            ->assertContent('');
    }
}
