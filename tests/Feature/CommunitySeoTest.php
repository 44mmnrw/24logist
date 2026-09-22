<?php

namespace Tests\Feature;

use App\Filament\Resources\CommunitySeoPages\CommunitySeoPageResource;
use App\Filament\Resources\CommunitySeoPages\Pages\EditCommunitySeoPage;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunitySeoPage;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Community\CommunitySeoRegistry;
use App\Services\SitemapService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CommunitySeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_admin_inventory_discovers_urls_and_saves_complete_metadata(): void
    {
        $post = $this->createPost();
        $this->get(CommunitySeoPageResource::getUrl())->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get(CommunitySeoPageResource::getUrl())
            ->assertOk()->assertSee('SEO страниц сообщества')->assertSee('/community/rules');
        $this->assertDatabaseHas('community_seo_pages', ['page_key' => 'post:'.$post->id]);
        $this->assertDatabaseHas('community_seo_pages', ['page_key' => 'category:'.$post->community_category_id]);
        $this->assertDatabaseHas('community_seo_pages', ['page_key' => 'profile:'.$post->community_user_id]);
        $page = CommunitySeoPage::where('page_key', 'community.index')->firstOrFail();

        Storage::fake('public');
        Livewire::test(EditCommunitySeoPage::class, ['record' => $page->id])
            ->fillForm(['settings' => [
                'meta_title' => 'Поисковый заголовок', 'meta_description' => 'Описание для поиска',
                'meta_keywords' => 'логистика, перевозки', 'seo_h1' => 'Форум экспедиторов',
                'og_title' => 'Заголовок для соцсетей', 'og_description' => 'Описание для соцсетей',
                'og_image_path' => UploadedFile::fake()->image('og.jpg', 1200, 630),
                'og_image_alt' => 'Обложка сообщества',
                'twitter_title' => 'Заголовок X', 'twitter_description' => 'Описание X',
                'twitter_card' => 'summary',
                'twitter_image_path' => UploadedFile::fake()->image('twitter.jpg', 600, 600),
                'schema_enabled' => true, 'schema_type' => 'CollectionPage',
                'schema_headline' => 'Сообщество специалистов', 'schema_description' => 'Описание Schema',
                'include_in_sitemap' => true,
            ]])->call('save')->assertHasNoFormErrors();

        $settings = $page->fresh()->settings;
        Storage::disk('public')->assertExists($settings['og_image_path']);
        Storage::disk('public')->assertExists($settings['twitter_image_path']);
        $response = $this->get('/community')->assertOk()
            ->assertSee('<title>Поисковый заголовок</title>', false)
            ->assertSee('name="description" content="Описание для поиска"', false)
            ->assertSee('property="og:description" content="Описание для соцсетей"', false)
            ->assertSee('property="og:title" content="Заголовок для соцсетей"', false)
            ->assertSee('name="twitter:description" content="Описание X"', false)
            ->assertSee('name="twitter:card" content="summary"', false)
            ->assertSee('property="og:image:alt" content="Обложка сообщества"', false)
            ->assertSee('Форум экспедиторов')
            ->assertSee('Сообщество специалистов')
            ->assertSee('Описание Schema');
        $this->assertSame(1, substr_count($response->getContent(), '<title>'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
        $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
    }

    public function test_overrides_survive_slug_changes_and_deletion_does_not_make_urls_indexable(): void
    {
        $post = $this->createPost();
        $page = CommunitySeoPage::where('page_key', 'post:'.$post->id)->firstOrFail();
        $page->update(['settings' => ['meta_title' => 'Ручной заголовок', 'seo_h1' => 'Другой H1']]);
        $post->update(['title' => 'Новое название', 'slug' => 'new-slug']);
        app(CommunitySeoRegistry::class)->sync();
        $this->assertStringEndsWith('/new-slug', $page->fresh()->path);
        $this->get($post->getUrl())->assertOk()->assertSee('<title>Ручной заголовок</title>', false)->assertSee('<h1>Другой H1</h1>', false);
        $this->get('/sitemap.xml')->assertSee($post->getUrl(), false);
        $post->delete();
        $this->assertFalse($page->fresh()->is_public);
        $this->get($post->getUrl())->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee($post->getUrl(), false);
        $post->restore();
        $this->assertTrue($page->fresh()->is_public);
        $this->get($post->getUrl())->assertOk()->assertSee('Ручной заголовок');
    }

    public function test_noindex_canonical_and_sitemap_switch_are_respected_and_cache_is_invalidated(): void
    {
        $post = $this->createPost();
        $page = CommunitySeoPage::where('page_key', 'post:'.$post->id)->firstOrFail();
        $this->get('/sitemap.xml')->assertSee($post->getUrl(), false);
        $page->update(['settings' => ['meta_robots' => 'noindex, follow']]);
        $this->get($post->getUrl())->assertSee('name="robots" content="noindex, follow"', false);
        $this->get('/sitemap.xml')->assertDontSee($post->getUrl(), false);
        $page->update(['settings' => ['canonical_url' => 'https://example.com/original']]);
        $this->get($post->getUrl())->assertSee('rel="canonical" href="https://example.com/original"', false);
        $this->get('/sitemap.xml')->assertDontSee($post->getUrl(), false)->assertDontSee('https://example.com/original', false);
        $page->update(['settings' => ['include_in_sitemap' => false]]);
        $this->get('/sitemap.xml')->assertDontSee($post->getUrl(), false);
        $page->update(['settings' => []]);
        $this->get('/sitemap.xml')->assertSee($post->getUrl(), false);
        $post->update(['meta_robots' => 'noindex, follow', 'seo_is_custom' => true]);
        $this->get('/sitemap.xml')->assertDontSee($post->getUrl(), false);
    }

    public function test_filters_and_private_pages_cannot_be_indexed(): void
    {
        app(CommunitySeoRegistry::class)->sync();
        CommunitySeoPage::where('page_key', 'community.login')->firstOrFail()->update(['settings' => ['meta_robots' => 'index, follow']]);
        $this->get('/community/login')->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
        foreach (['q=truck', 'sort=new', 'page=2', 'period=month'] as $query) {
            $this->get('/community?'.$query)->assertOk()
                ->assertSee('name="robots" content="noindex, follow"', false)
                ->assertSee('rel="canonical" href="'.route('community.index').'"', false);
        }
        $this->get('/sitemap.xml')->assertDontSee('/community/login', false)->assertDontSee('/community/settings', false);
    }

    public function test_categories_legal_pages_profiles_and_schema_use_overrides_safely(): void
    {
        $post = $this->createPost();
        app(CommunitySeoRegistry::class)->sync();
        $targets = [
            'category:'.$post->community_category_id => route('community.categories.show', $post->category),
            'community.rules' => route('community.rules'),
            'community.privacy' => route('community.privacy'),
            'profile:'.$post->community_user_id => route('community.profile', $post->author),
        ];
        foreach ($targets as $key => $url) {
            $page = CommunitySeoPage::where('page_key', $key)->firstOrFail();
            $page->update(['settings' => ['meta_title' => 'Особый title', 'seo_h1' => 'Особый H1', 'schema_headline' => '</script><script>alert(1)</script>']]);
            $response = $this->get($url)->assertOk()->assertSee('<title>Особый title</title>', false)->assertSee('Особый H1');
            $response->assertDontSee('</script><script>alert(1)</script>', false);
            preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response->getContent(), $matches);
            $graph = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('</script><script>alert(1)</script>', $graph['name']);
            $page->update(['settings' => ['schema_enabled' => false]]);
            $this->get($url)->assertDontSee('application/ld+json', false);
        }
        $this->get(route('community.profile', $post->author))->assertSee('name="robots" content="noindex, follow"', false);
        $post->category->update(['is_active' => false]);
        $this->get(route('community.categories.show', $post->category))->assertNotFound();
        $this->assertNotContains(route('community.categories.show', $post->category), array_column(app(SitemapService::class)->urls(), 'loc'));
    }

    public function test_new_user_without_username_does_not_break_registration(): void
    {
        $user = CommunityUser::factory()->create(['username' => '', 'display_name' => 'Новый участник', 'onboarded_at' => null, 'terms_accepted_at' => null]);
        $this->assertDatabaseMissing('community_seo_pages', ['page_key' => 'profile:'.$user->id]);
        $user->update(['username' => 'new-user', 'onboarded_at' => now(), 'terms_accepted_at' => now()]);
        $this->assertDatabaseHas('community_seo_pages', ['page_key' => 'profile:'.$user->id, 'is_public' => true]);
    }

    public function test_legacy_manual_metadata_retains_automatic_canonical_after_slug_change(): void
    {
        $post = $this->createPost();
        $post->update(['seo_is_custom' => true, 'meta_title' => 'Старый ручной title']);
        $post->update(['slug' => 'updated-link']);
        $this->assertSame($post->getUrl(), $post->fresh()->canonical_url);
        $this->get($post->getUrl())->assertSee('<title>Старый ручной title</title>', false)
            ->assertSee('rel="canonical" href="'.$post->getUrl().'"', false);
        $this->get('/sitemap.xml')->assertSee($post->getUrl(), false);
        $post->update(['canonical_url' => 'https://example.com/source']);
        $post->update(['slug' => 'another-link']);
        $this->assertSame('https://example.com/source', $post->fresh()->canonical_url);
    }

    public function test_filter_noindex_preserves_manual_nofollow(): void
    {
        app(CommunitySeoRegistry::class)->sync();
        CommunitySeoPage::where('page_key', 'community.index')->firstOrFail()->update(['settings' => ['meta_robots' => 'index, nofollow']]);
        $this->get('/community?sort=new')->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    private function createPost(): CommunityPost
    {
        return CommunityPost::create([
            'community_user_id' => CommunityUser::factory()->create()->id,
            'community_category_id' => CommunityCategory::firstOrFail()->id,
            'title' => 'Документы в рейсе', 'slug' => 'documents', 'body_html' => '<p>Текст темы</p>',
            'body_markdown' => 'Текст темы', 'published_at' => now(), 'status' => 'published',
        ]);
    }
}
