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
use App\Support\CommunityText;
use Carbon\Carbon;
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

    public function test_comment_composer_has_rich_editor_and_media_button(): void
    {
        $post = new CommunityPost;
        $post->id = 1;
        $html = view('community.comments._form', ['post' => $post, 'parent' => null])->render();

        $this->assertStringContainsString('data-rich-editor', $html);
        $this->assertStringContainsString('data-rich-editor-surface', $html);
        $this->assertStringContainsString('data-rich-format="bold"', $html);
        $this->assertStringContainsString('data-composer-photo-trigger', $html);
        $this->assertStringContainsString('#tabler-photo-up', $html);
        $this->assertStringNotContainsString('data-markdown-preview-toggle', $html);
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

    public function test_comment_report_rejects_post_only_action_without_resolving_report(): void
    {
        $author = CommunityUser::factory()->create();
        $moderator = CommunityUser::factory()->create(['role' => 'moderator']);
        $post = $this->postBy($author);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id, 'community_user_id' => $author->id,
            'body_markdown' => 'Ответ', 'body_html' => '<p>Ответ</p>',
        ]);
        $report = CommunityReport::query()->create([
            'community_user_id' => $author->id, 'target_type' => 'comment',
            'target_id' => $comment->id, 'reason' => 'spam',
        ]);

        $this->actingAs($moderator, 'community')
            ->post(route('community.moderation.act', $report), ['action' => 'pin'])
            ->assertSessionHasErrors('action');

        $this->assertSame('open', $report->fresh()->status);
        $this->assertDatabaseCount('community_moderation_actions', 0);
    }

    public function test_hidden_comment_changes_count_and_deleted_comment_cannot_be_restored(): void
    {
        $author = CommunityUser::factory()->create();
        $moderator = CommunityUser::factory()->create(['role' => 'moderator']);
        $post = $this->postBy($author);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id, 'community_user_id' => $author->id,
            'body_markdown' => 'Ответ', 'body_html' => '<p>Ответ</p>',
        ]);
        $post->update(['comments_count' => 1]);
        $post->update(['accepted_comment_id' => $comment->id, 'resolved_at' => now()]);
        $report = CommunityReport::query()->create([
            'community_user_id' => $author->id, 'target_type' => 'comment',
            'target_id' => $comment->id, 'reason' => 'spam',
        ]);

        $this->actingAs($moderator, 'community')
            ->post(route('community.moderation.act', $report), ['action' => 'hide'])
            ->assertRedirect();
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->assertNull($post->fresh()->accepted_comment_id);

        $comment->update(['status' => 'deleted', 'body_markdown' => null, 'body_html' => null]);
        $secondReport = CommunityReport::query()->create([
            'community_user_id' => $author->id, 'target_type' => 'comment',
            'target_id' => $comment->id, 'reason' => 'abuse',
        ]);
        $this->post(route('community.moderation.act', $secondReport), ['action' => 'restore'])
            ->assertSessionHasErrors('action');
        $this->assertSame('open', $secondReport->fresh()->status);
    }

    public function test_cannot_reply_to_hidden_comment_or_delete_comment_twice(): void
    {
        $author = CommunityUser::factory()->create();
        $post = $this->postBy($author);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id, 'community_user_id' => $author->id,
            'body_markdown' => 'Ответ', 'body_html' => '<p>Ответ</p>',
            'status' => 'hidden',
        ]);

        $this->actingAs($author, 'community')
            ->post(route('community.comments.store', $post), ['parent_id' => $comment->id, 'body_markdown' => 'Ответ на скрытое'])
            ->assertNotFound();

        $comment->update(['status' => 'published']);
        $post->update(['comments_count' => 1]);
        $this->delete(route('community.comments.destroy', $comment))->assertRedirect();
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->actingAs(CommunityUser::factory()->create(['role' => 'moderator']), 'community')
            ->delete(route('community.comments.destroy', $comment))->assertNotFound();
        $this->assertSame(0, $post->fresh()->comments_count);
    }

    public function test_hidden_parent_is_a_tombstone_so_existing_replies_stay_visible(): void
    {
        $author = CommunityUser::factory()->create();
        $post = $this->postBy($author);
        $root = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'body_markdown' => 'Скрытый текст',
            'body_html' => '<p>Скрытый текст</p>',
            'status' => 'hidden',
        ]);
        $root->update(['root_id' => $root->id]);
        CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'parent_id' => $root->id,
            'root_id' => $root->id,
            'depth' => 1,
            'body_markdown' => 'Полезный ответ',
            'body_html' => '<p>Полезный ответ</p>',
        ]);

        $this->get($post->getUrl())
            ->assertOk()
            ->assertSee('Комментарий скрыт модератором.')
            ->assertSee('Полезный ответ')
            ->assertDontSee('Скрытый текст');
    }

    public function test_subscriber_receives_replies_until_unsubscribed(): void
    {
        $author = CommunityUser::factory()->create();
        $subscriber = CommunityUser::factory()->create();
        $writer = CommunityUser::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($subscriber, 'community')
            ->post(route('community.posts.subscribe', $post))->assertRedirect($post->getUrl());
        $this->assertDatabaseHas('community_post_subscriptions', [
            'community_post_id' => $post->id, 'community_user_id' => $subscriber->id,
        ]);

        $this->actingAs($writer, 'community')
            ->post(route('community.comments.store', $post), ['body_markdown' => 'Первый ответ'])
            ->assertRedirect();
        $this->assertDatabaseHas('community_notifications', [
            'community_user_id' => $subscriber->id, 'type' => 'post_reply',
        ]);

        $this->actingAs($subscriber, 'community')
            ->delete(route('community.posts.unsubscribe', $post))->assertRedirect($post->getUrl());
        $this->actingAs($writer, 'community')
            ->post(route('community.comments.store', $post), ['body_markdown' => 'Второй ответ'])
            ->assertRedirect();
        $this->assertSame(1, $subscriber->communityNotifications()->count());
    }

    public function test_post_author_can_accept_answer_and_deleting_it_clears_resolution(): void
    {
        $author = CommunityUser::factory()->create();
        $responder = CommunityUser::factory()->create();
        $stranger = CommunityUser::factory()->create();
        $post = $this->postBy($author);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $responder->id,
            'body_markdown' => 'Решение',
            'body_html' => '<p>Решение</p>',
        ]);

        $this->actingAs($stranger, 'community')
            ->post(route('community.posts.accept_answer', [$post, $comment]))->assertForbidden();
        $this->actingAs($author, 'community')
            ->post(route('community.posts.accept_answer', [$post, $comment]))->assertRedirect();
        $this->assertSame($comment->id, $post->fresh()->accepted_comment_id);
        $this->assertDatabaseHas('community_notifications', [
            'community_user_id' => $responder->id, 'type' => 'answer_accepted',
        ]);
        $this->get($post->getUrl())->assertOk()->assertSee('Принятый ответ');

        $this->actingAs($responder, 'community')
            ->delete(route('community.comments.destroy', $comment))->assertRedirect();
        $this->assertNull($post->fresh()->accepted_comment_id);
        $this->assertNull($post->fresh()->resolved_at);
    }

    public function test_authenticated_reader_sees_functional_report_controls(): void
    {
        $author = CommunityUser::factory()->create();
        $reader = CommunityUser::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($reader, 'community')
            ->get($post->getUrl())
            ->assertOk()
            ->assertSee('data-report-open', false)
            ->assertSee('data-report-dialog', false)
            ->assertSee('Отправить жалобу');
    }

    public function test_feed_card_has_a_full_card_link_to_the_topic(): void
    {
        $post = $this->postBy(CommunityUser::factory()->create());

        $this->get(route('community.index'))
            ->assertOk()
            ->assertSee('community-post-card__overlay', false)
            ->assertSee('href="'.$post->getUrl().'"', false)
            ->assertSee('aria-labelledby="community-post-title-'.$post->id.'"', false)
            ->assertSee('data-vote', false)
            ->assertSee('tabler-sprite.svg#tabler-arrow-big-up-lines', false)
            ->assertSee('tabler-sprite.svg#tabler-arrow-big-down-lines', false)
            ->assertSee('data-share-url="'.$post->getUrl().'"', false)
            ->assertSee('community-action-chip--comments', false);
    }

    public function test_feed_search_finds_only_matching_published_topics(): void
    {
        $author = CommunityUser::factory()->create();
        $matching = $this->postBy($author);
        $matching->update(['title' => 'Как оформить ЭТрН']);
        $other = $this->postBy($author);
        $other->update(['title' => 'Работа с водителями']);

        $this->get(route('community.index', ['q' => 'ЭТрН']))
            ->assertOk()
            ->assertSeeInOrder(['<header class="community-toolbar">', '<form class="community-search community-toolbar__search"', '</header>', '<main class="community-main">'], false)
            ->assertSee('action="'.route('community.index').'"', false)
            ->assertSee('value="ЭТрН"', false)
            ->assertSee('Как оформить ЭТрН')->assertDontSee('Работа с водителями');
        $this->get(route('community.categories.show', ['category' => $matching->category, 'q' => 'ЭТрН']))
            ->assertOk()
            ->assertSee('action="'.route('community.categories.show', $matching->category).'"', false)
            ->assertSee('Как оформить ЭТрН');
        $this->get(route('community.index', ['q' => 'несуществующий запрос']))
            ->assertOk()->assertSee('Ничего не найдено');
    }

    public function test_russian_comment_plural_forms(): void
    {
        $this->assertSame('комментариев', CommunityText::comments(0));
        $this->assertSame('комментарий', CommunityText::comments(1));
        $this->assertSame('комментария', CommunityText::comments(2));
        $this->assertSame('комментариев', CommunityText::comments(5));
        $this->assertSame('комментариев', CommunityText::comments(11));
        $this->assertSame('комментариев', CommunityText::comments(12));
        $this->assertSame('комментарий', CommunityText::comments(21));
    }

    public function test_topic_comments_can_be_sorted_like_a_discussion_feed(): void
    {
        $author = CommunityUser::factory()->create();
        $post = $this->postBy($author);
        CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'depth' => 0,
            'body_markdown' => 'Старый ответ',
            'body_html' => '<p>Старый ответ</p>',
            'status' => 'published',
        ]);
        CommunityComment::query()->where('body_markdown', 'Старый ответ')->update(['created_at' => now()->subHour()]);
        CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'depth' => 0,
            'body_markdown' => 'Новый ответ',
            'body_html' => '<p>Новый ответ</p>',
            'status' => 'published',
            'created_at' => now(),
        ]);

        $this->get($post->getUrl().'?comment_sort=new')
            ->assertOk()
            ->assertSeeInOrder(['Новый ответ', 'Старый ответ'])
            ->assertSee('community-comments__toolbar', false);

        $this->get($post->getUrl().'?comment_sort=old')
            ->assertOk()
            ->assertSeeInOrder(['Старый ответ', 'Новый ответ']);
    }

    public function test_community_dates_are_always_displayed_in_russian(): void
    {
        app()->setLocale('en');
        Carbon::setLocale('en');

        $user = CommunityUser::factory()->create([
            'username' => 'russian_date',
            'onboarded_at' => Carbon::create(2026, 9, 2, 12),
        ]);
        $post = $this->postBy($user);
        $post->update(['published_at' => now()->subSeconds(30)]);

        $user->forceFill(['created_at' => Carbon::create(2026, 9, 2, 12)])->save();
        $this->get(route('community.profile', $user))
            ->assertOk()
            ->assertSee('зарегистрирован 2 сентября 2026')
            ->assertDontSee('September 2026');

        $this->get(route('community.index'))
            ->assertOk()
            ->assertSee('30 секунд назад')
            ->assertDontSee('seconds ago');
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
