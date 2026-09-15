<?php

namespace Tests\Feature;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitySocialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_unanswered_feed_excludes_topics_with_published_replies(): void
    {
        $author = CommunityUser::factory()->create();
        $waiting = $this->postBy($author, 'Нужен совет по перевозке');
        $answered = $this->postBy($author, 'Уже есть ответ');
        CommunityComment::query()->create([
            'community_post_id' => $answered->id,
            'community_user_id' => CommunityUser::factory()->create()->id,
            'root_id' => null,
            'body_markdown' => 'Решение',
            'body_html' => '<p>Решение</p>',
        ]);

        $this->get(route('community.index', ['sort' => 'unanswered']))
            ->assertOk()->assertSee($waiting->title)->assertDontSee($answered->title);
    }

    public function test_up_to_three_reactions_can_be_selected_and_removed_without_affecting_votes_or_karma(): void
    {
        $author = CommunityUser::factory()->create();
        $reader = CommunityUser::factory()->create();
        $post = $this->postBy($author, 'Как лучше оформить перевозку');

        $this->actingAs($reader, 'community')
            ->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'useful'])
            ->assertOk()->assertJsonPath('reactions.useful', 1)->assertJsonPath('selected.0', 'useful');
        $this->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'thanks'])
            ->assertOk()->assertJsonPath('reactions.thanks', 1)->assertJsonCount(2, 'selected');
        $this->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'same'])
            ->assertOk()->assertJsonPath('reactions.same', 1)->assertJsonCount(3, 'selected');
        $this->assertDatabaseCount('community_reactions', 3);
        $this->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'frustrated'])
            ->assertUnprocessable()->assertJsonPath('message', 'Можно выбрать не более трёх реакций.');
        $this->assertDatabaseMissing('community_reactions', ['code' => 'frustrated']);
        $this->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'thanks'])
            ->assertOk()->assertJsonPath('selected.0', 'useful')->assertJsonPath('selected.1', 'same')->assertJsonCount(2, 'selected');
        $this->assertDatabaseCount('community_reactions', 2);
        $this->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'frustrated'])
            ->assertOk()->assertJsonPath('reactions.frustrated', 1)->assertJsonCount(3, 'selected');
        $this->assertDatabaseCount('community_reactions', 3);
        $this->assertSame(1, $post->fresh()->score);
        $this->assertSame(0, $author->fresh()->karma);

        $this->actingAs($author, 'community')
            ->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'useful'])
            ->assertForbidden();
        $this->actingAs($reader, 'community')
            ->postJson(route('community.react'), ['target_type' => 'post', 'target_id' => $post->id, 'code' => 'unknown'])
            ->assertUnprocessable();
    }

    public function test_free_award_is_visible_once_and_anonymous_to_recipient(): void
    {
        $author = CommunityUser::factory()->create(['username' => 'helpful_author']);
        $reader = CommunityUser::factory()->create(['username' => 'thankful_reader']);
        $post = $this->postBy($author, 'Полезная инструкция');

        $this->actingAs($reader, 'community')->post(route('community.award'), [
            'target_type' => 'post', 'target_id' => $post->id, 'code' => 'gold',
            'message' => 'Очень помогло', 'is_anonymous' => 1,
        ])->assertRedirect($post->getUrl());

        $this->assertDatabaseHas('community_awards', [
            'community_user_id' => $reader->id, 'target_type' => 'post',
            'target_id' => $post->id, 'code' => 'gold', 'is_anonymous' => 1,
        ]);
        $this->assertDatabaseCount('community_notifications', 1);
        $notification = $author->communityNotifications()->firstOrFail();
        $this->assertNull($notification->actor_id);
        $this->assertStringNotContainsString('thankful_reader', $notification->data['message']);
        $this->assertSame('Очень помогло', $notification->data['body']);
        $this->assertSame('Вам анонимно вручили награду «Золотой ответ»', $notification->data['title']);

        $this->post(route('community.award'), [
            'target_type' => 'post', 'target_id' => $post->id, 'code' => 'fire',
        ])->assertRedirect();
        $this->assertDatabaseCount('community_awards', 1);
        $this->get($post->getUrl())->assertOk()->assertSee('Золотой ответ')->assertSee('Удалить награду');
        $this->actingAs($author, 'community')->delete(route('community.award.destroy'), [
            'target_type' => 'post', 'target_id' => $post->id,
        ])->assertRedirect($post->getUrl());
        $this->assertDatabaseCount('community_awards', 1);
        $this->assertDatabaseCount('community_notifications', 1);
        $this->actingAs($reader, 'community')->delete(route('community.award.destroy'), [
            'target_type' => 'post', 'target_id' => $post->id,
        ])->assertRedirect($post->getUrl());
        $this->assertDatabaseCount('community_awards', 0);
        $this->assertDatabaseCount('community_notifications', 0);
        $this->actingAs($author, 'community')->post(route('community.award'), [
            'target_type' => 'post', 'target_id' => $post->id, 'code' => 'gold',
        ])->assertForbidden();
    }

    public function test_hidden_comment_cannot_receive_reactions_or_awards(): void
    {
        $author = CommunityUser::factory()->create();
        $reader = CommunityUser::factory()->create();
        $post = $this->postBy($author, 'Вопрос по ЭПД');
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'body_markdown' => 'Опыт работы',
            'body_html' => '<p>Опыт работы</p>',
        ]);

        $this->actingAs($reader, 'community')->postJson(route('community.react'), [
            'target_type' => 'comment', 'target_id' => $comment->id, 'code' => 'thanks',
        ])->assertOk()->assertJsonPath('reactions.thanks', 1);
        $this->post(route('community.award'), [
            'target_type' => 'comment', 'target_id' => $comment->id, 'code' => 'applause',
        ])->assertRedirect($post->getUrl().'#comment-'.$comment->id);

        $comment->update(['status' => 'hidden']);
        $this->postJson(route('community.react'), [
            'target_type' => 'comment', 'target_id' => $comment->id, 'code' => 'same',
        ])->assertNotFound();
        $this->post(route('community.award'), [
            'target_type' => 'comment', 'target_id' => $comment->id, 'code' => 'gold',
        ])->assertNotFound();
    }

    private function postBy(CommunityUser $author, string $title): CommunityPost
    {
        return CommunityPost::query()->create([
            'community_user_id' => $author->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'slug' => 'social-'.uniqid(),
            'title' => $title,
            'body_markdown' => 'Текст публикации',
            'body_html' => '<p>Текст публикации</p>',
            'published_at' => now(),
            'hot_score' => 1,
        ]);
    }
}
