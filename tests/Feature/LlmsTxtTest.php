<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use App\Models\SiteSetting;
use App\Services\LlmsTxtService;
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

    public function test_it_refreshes_llms_txt_from_published_pages_articles_and_used_tags(): void
    {
        SiteSetting::instance()->update([
            'org_brand_name' => 'ЛогистРу',
            'ai_site_summary' => 'CRM для транспортных экспедиторов.',
            'blog_description' => 'Материалы о цифровой логистике.',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $page = CmsPage::query()->create([
            'slug' => 'documentation',
            'title' => 'Документация',
            'meta_description' => 'Инструкции по работе с системой.',
            'is_published' => true,
        ]);
        CmsPage::query()->create([
            'slug' => 'draft-page',
            'title' => 'Черновик страницы',
            'is_published' => false,
        ]);

        $tag = BlogTag::query()->create([
            'name' => 'ЭТрН',
            'slug' => 'etrn',
            'seo_h1' => 'Электронные транспортные накладные',
            'meta_description' => 'Материалы об ЭТрН для экспедиторов.',
        ]);
        BlogTag::query()->create(['name' => 'Неиспользуемый тег', 'slug' => 'unused']);

        $post = BlogPost::query()->create([
            'slug' => 'etrn-for-forwarders',
            'title' => 'ЭТрН для экспедиторов',
            'excerpt' => 'Практическое руководство по ЭТрН.',
            'tags' => ['ЭТрН'],
            'is_published' => true,
            'published_at' => now(),
        ]);
        BlogPost::query()->create([
            'slug' => 'draft-post',
            'title' => 'Черновик статьи',
            'tags' => ['Неиспользуемый тег'],
            'is_published' => false,
        ]);

        $result = app(LlmsTxtService::class)->refreshFromPublishedContent();

        $this->assertSame(1, $result['pages']);
        $this->assertSame(1, $result['posts']);
        $this->assertSame(1, $result['tags']);
        $this->assertStringContainsString('[Документация]('.route('pages.show', $page->slug).')', $result['content']);
        $this->assertStringContainsString('[ЭТрН для экспедиторов]('.$post->getUrl().')', $result['content']);
        $this->assertStringContainsString('('.$tag->getUrl().')', $result['content']);
        $this->assertStringNotContainsString('Черновик страницы', $result['content']);
        $this->assertStringNotContainsString('Черновик статьи', $result['content']);
        $this->assertStringNotContainsString('Неиспользуемый тег', $result['content']);
        $this->assertSame($result['content'], SiteSetting::instance()->fresh()->llms_txt_extra);
        $this->assertSame($result['content'], app(LlmsTxtService::class)->generate());
    }
}
