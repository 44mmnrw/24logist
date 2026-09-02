<?php

namespace Tests\Feature;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityReport;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_guests_can_read_feed_but_cannot_publish(): void
    {
        $category = CommunityCategory::query()->firstOrFail();
        $user = CommunityUser::factory()->create(['username' => 'logist']);
        CommunityPost::query()->create([
            'community_user_id' => $user->id,
            'community_category_id' => $category->id,
            'slug' => 'first-topic',
            'title' => 'Первая тема сообщества',
            'body_markdown' => 'Полезный текст',
            'body_html' => '<p>Полезный текст</p>',
            'published_at' => now(),
            'hot_score' => 1,
        ]);

        $this->get('/community')->assertOk()->assertSee('Первая тема сообщества');
        $this->get('/community/submit')->assertRedirect(route('community.login'));
        $this->assertDatabaseCount('community_categories', 5);
    }

    public function test_feature_flag_hides_all_public_community_pages(): void
    {
        SiteSetting::instance()->update(['community_enabled' => false]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/community')->assertNotFound();
        $this->get('/community/login')->assertNotFound();
    }

    public function test_onboarding_normalizes_username_and_requires_terms(): void
    {
        $user = CommunityUser::query()->create(['username' => 'telegram-placeholder']);

        $this->actingAs($user, 'community')->get(route('community.onboarding'))->assertOk();
        $this->post(route('community.onboarding.store'), ['username' => 'New_Logist'])->assertSessionHasErrors('accept_terms');
        $this->post(route('community.onboarding.store'), ['username' => 'New_Logist', 'accept_terms' => 1])->assertRedirect();

        $this->assertSame('new_logist', $user->fresh()->username);
        $this->assertTrue($user->fresh()->isOnboarded());
    }

    public function test_community_guard_does_not_authenticate_filament(): void
    {
        $user = CommunityUser::factory()->create();

        $this->actingAs($user, 'community')->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($user, 'community');
    }

    public function test_onboarded_user_can_create_safe_markdown_post_with_initial_vote(): void
    {
        $user = CommunityUser::factory()->create(['username' => 'driver_77']);
        $category = CommunityCategory::query()->firstOrFail();

        $response = $this->actingAs($user, 'community')->post(route('community.posts.store'), [
            'community_category_id' => $category->id,
            'title' => 'Как оформить перевозку?',
            'body_markdown' => '**Вопрос** <script>alert(1)</script> [ссылка](javascript:alert(1))',
        ]);

        $post = CommunityPost::query()->firstOrFail();
        $response->assertRedirect($post->getUrl());
        $this->assertStringContainsString('<strong>Вопрос</strong>', $post->body_html);
        $this->assertStringNotContainsString('<script', $post->body_html);
        $this->assertStringNotContainsString('javascript:', $post->body_html);
        $this->assertSame(1, $post->score);
        $this->assertDatabaseHas('community_post_votes', ['community_user_id' => $user->id, 'community_post_id' => $post->id, 'value' => 1]);
    }

    public function test_vote_requests_are_idempotent_and_update_author_karma(): void
    {
        $author = CommunityUser::factory()->create();
        $voter = CommunityUser::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($voter, 'community')->postJson(route('community.vote'), ['target_type' => 'post', 'target_id' => $post->id, 'value' => 1])
            ->assertOk()->assertJson(['score' => 2, 'user_vote' => 1]);
        $this->postJson(route('community.vote'), ['target_type' => 'post', 'target_id' => $post->id, 'value' => 1])
            ->assertOk()->assertJson(['score' => 2, 'user_vote' => 1]);

        $this->assertSame(2, $post->fresh()->score);
        $this->assertSame(1, $author->fresh()->karma);
    }

    public function test_comments_are_nested_limited_and_create_one_notification(): void
    {
        $author = CommunityUser::factory()->create(['username' => 'owner']);
        $commenter = CommunityUser::factory()->create(['username' => 'responder']);
        $post = $this->postBy($author);

        $this->actingAs($commenter, 'community')->post(route('community.comments.store', $post), ['body_markdown' => 'Первый ответ'])
            ->assertRedirect();
        $root = CommunityComment::query()->firstOrFail();
        $this->assertSame($root->id, $root->root_id);
        $this->assertSame(1, $post->fresh()->comments_count);
        $this->assertDatabaseCount('community_notifications', 1);

        $parent = $root;
        for ($depth = 1; $depth < 6; $depth++) {
            $this->post(route('community.comments.store', $post), ['body_markdown' => 'Уровень '.$depth, 'parent_id' => $parent->id])->assertRedirect();
            $parent = CommunityComment::query()->latest('id')->firstOrFail();
            $this->assertSame($depth, $parent->depth);
        }

        $this->post(route('community.comments.store', $post), ['body_markdown' => 'Слишком глубоко', 'parent_id' => $parent->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_reports_are_unique_while_open_and_moderator_can_hide_target(): void
    {
        $author = CommunityUser::factory()->create();
        $reporter = CommunityUser::factory()->create();
        $moderator = CommunityUser::factory()->create(['role' => 'moderator']);
        $post = $this->postBy($author);

        $this->actingAs($reporter, 'community')->post(route('community.report'), [
            'target_type' => 'post', 'target_id' => $post->id, 'reason' => 'spam',
        ])->assertRedirect();
        $this->post(route('community.report'), [
            'target_type' => 'post', 'target_id' => $post->id, 'reason' => 'spam',
        ])->assertSessionHasErrors('reason');

        $report = CommunityReport::query()->firstOrFail();
        $this->actingAs($moderator, 'community')->post(route('community.moderation.act', $report), ['action' => 'hide', 'reason' => 'Реклама'])
            ->assertRedirect();

        $this->assertSame('hidden', $post->fresh()->status);
        $this->assertSame('actioned', $report->fresh()->status);
        $this->assertDatabaseHas('community_moderation_actions', ['community_user_id' => $moderator->id, 'action' => 'hide']);
    }

    public function test_sitemap_contains_only_public_community_routes(): void
    {
        $post = $this->postBy(CommunityUser::factory()->create());

        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertSee(route('community.index'), false);
        $response->assertSee($post->getUrl(), false);
        $response->assertDontSee('/community/settings', false);
    }

    public function test_account_deletion_removes_identity_and_anonymizes_content(): void
    {
        $user = CommunityUser::factory()->create();
        $user->identities()->create(['provider' => 'telegram', 'provider_user_id' => '7788', 'last_verified_at' => now()]);
        $post = $this->postBy($user);

        $this->actingAs($user, 'community')->delete(route('community.settings.destroy'), ['confirmation' => 'УДАЛИТЬ'])
            ->assertRedirect(route('community.index'));

        $this->assertDatabaseMissing('community_users', ['id' => $user->id]);
        $this->assertDatabaseMissing('community_identities', ['provider_user_id' => '7788']);
        $this->assertNull($post->fresh()->community_user_id);
        $this->assertGuest('community');
    }

    private function postBy(CommunityUser $author): CommunityPost
    {
        $post = CommunityPost::query()->create([
            'community_user_id' => $author->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'slug' => 'test-topic-'.uniqid(),
            'title' => 'Тестовая тема',
            'body_markdown' => 'Текст',
            'body_html' => '<p>Текст</p>',
            'published_at' => now(),
            'hot_score' => 1,
        ]);
        CommunityPostVote::query()->create(['community_user_id' => $author->id, 'community_post_id' => $post->id, 'value' => 1]);

        return $post;
    }
}
