<?php

namespace Tests\Feature;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        Storage::fake('local');
        $this->withoutVite();
    }

    public function test_photo_only_post_is_reencoded_and_visible_in_feed_and_topic(): void
    {
        $user = CommunityUser::factory()->create();
        $this->actingAs($user, 'community')->post(route('community.posts.store'), [
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'title' => 'Фото погрузки',
            'photos' => [UploadedFile::fake()->image('loading.png', 2400, 1200)],
        ])->assertRedirect();

        $post = CommunityPost::query()->firstOrFail();
        $photo = $post->photos()->firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $image = getimagesizefromstring(Storage::disk('local')->get($photo->path));
        $this->assertSame('image/webp', $image['mime']);
        $this->assertSame(1920, $image[0]);
        $this->assertSame(960, $image[1]);
        $this->get($photo->getUrl())->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get(route('community.index'))->assertOk()->assertSee($photo->getUrl(), false);
        $this->get($post->getUrl())->assertOk()->assertSee($photo->getUrl(), false);
    }

    public function test_post_owner_can_replace_photo_but_cannot_remove_another_posts_photo(): void
    {
        $user = CommunityUser::factory()->create();
        $post = $this->postBy($user);
        $foreign = $this->postBy(CommunityUser::factory()->create());
        $old = $post->photos()->create(['path' => 'community/photos/old.webp', 'width' => 100, 'height' => 100, 'position' => 0]);
        $foreignPhoto = $foreign->photos()->create(['path' => 'community/photos/foreign.webp', 'width' => 100, 'height' => 100, 'position' => 0]);
        Storage::disk('local')->put($old->path, 'old');

        $payload = ['community_category_id' => $post->community_category_id, 'title' => $post->title, 'body_markdown' => 'Текст'];
        $this->actingAs($user, 'community')->put(route('community.posts.update', $post), $payload + [
            'remove_photos' => [$foreignPhoto->id],
        ])->assertSessionHasErrors('remove_photos');
        $this->assertDatabaseHas('community_photos', ['id' => $old->id]);

        $this->put(route('community.posts.update', $post), $payload + [
            'remove_photos' => [$old->id],
            'photos' => [UploadedFile::fake()->image('replacement.jpg', 800, 600)],
        ])->assertRedirect($post->fresh()->getUrl());

        Storage::disk('local')->assertMissing($old->path);
        $this->assertSame(1, $post->photos()->count());
        Storage::disk('local')->assertExists($post->photos()->firstOrFail()->path);
    }

    public function test_comment_photo_is_hidden_with_moderation_and_deleted_with_comment(): void
    {
        $user = CommunityUser::factory()->create();
        $post = $this->postBy($user);

        $this->actingAs($user, 'community')->post(route('community.comments.store', $post), [
            'photos' => [UploadedFile::fake()->image('reply.jpg', 500, 400)],
        ])->assertRedirect();

        $comment = CommunityComment::query()->firstOrFail();
        $photo = $comment->photos()->firstOrFail();
        $this->assertNull($comment->body_markdown);
        $this->get($photo->getUrl())->assertOk();

        $comment->update(['status' => 'hidden']);
        $this->get($photo->getUrl())->assertNotFound();
        $moderator = CommunityUser::factory()->create(['role' => 'moderator']);
        $this->actingAs($moderator, 'community')->get($photo->getUrl())->assertOk();
        $this->actingAs($user, 'community');
        $comment->update(['status' => 'published']);
        $this->get($photo->getUrl())->assertOk();

        $this->delete(route('community.comments.destroy', $comment))->assertRedirect();
        $this->get($photo->getUrl())->assertNotFound();
        $this->assertDatabaseMissing('community_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing($photo->path);
    }

    public function test_deleting_post_removes_its_photos_and_comment_photos(): void
    {
        $user = CommunityUser::factory()->create();
        $post = $this->postBy($user);
        $postPhoto = $post->photos()->create(['path' => 'community/photos/post.webp', 'width' => 100, 'height' => 100, 'position' => 0]);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id, 'community_user_id' => $user->id, 'root_id' => 1,
            'depth' => 0, 'body_markdown' => 'Ответ', 'body_html' => '<p>Ответ</p>',
        ]);
        $commentPhoto = $comment->photos()->create(['path' => 'community/photos/comment.webp', 'width' => 100, 'height' => 100, 'position' => 0]);
        Storage::disk('local')->put($postPhoto->path, 'post');
        Storage::disk('local')->put($commentPhoto->path, 'comment');

        $this->actingAs($user, 'community')->delete(route('community.posts.destroy', $post))->assertRedirect(route('community.index'));

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
        $this->assertDatabaseCount('community_photos', 0);
        Storage::disk('local')->assertMissing($postPhoto->path);
        Storage::disk('local')->assertMissing($commentPhoto->path);
        $this->get($postPhoto->getUrl())->assertNotFound();
    }

    public function test_comment_edit_requires_content_when_last_photo_is_removed(): void
    {
        $user = CommunityUser::factory()->create();
        $post = $this->postBy($user);
        $this->actingAs($user, 'community')->post(route('community.comments.store', $post), [
            'photos' => [UploadedFile::fake()->image('answer.png', 320, 240)],
        ])->assertRedirect();

        $comment = CommunityComment::query()->firstOrFail();
        $photo = $comment->photos()->firstOrFail();
        $this->put(route('community.comments.update', $comment), [
            'remove_photos' => [$photo->id],
        ])->assertSessionHasErrors('body_markdown');
        Storage::disk('local')->assertExists($photo->path);

        $this->put(route('community.comments.update', $comment), [
            'body_markdown' => 'Пояснение вместо фото',
            'remove_photos' => [$photo->id],
        ])->assertRedirect();
        $this->assertDatabaseMissing('community_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing($photo->path);
    }

    public function test_invalid_or_too_many_photos_are_rejected(): void
    {
        $user = CommunityUser::factory()->create();
        $payload = ['community_category_id' => CommunityCategory::query()->firstOrFail()->id, 'title' => 'Фото'];

        $this->actingAs($user, 'community')->post(route('community.posts.store'), $payload + [
            'photos' => array_fill(0, 6, UploadedFile::fake()->image('photo.jpg', 20, 20)),
        ])->assertSessionHasErrors('photos');

        $this->post(route('community.posts.store'), $payload + [
            'photos' => [UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('community_photos', 0);
    }

    private function postBy(CommunityUser $user): CommunityPost
    {
        return CommunityPost::query()->create([
            'community_user_id' => $user->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'slug' => 'photo-topic',
            'title' => 'Тема с фото',
            'body_markdown' => 'Текст',
            'body_html' => '<p>Текст</p>',
            'published_at' => now(),
        ]);
    }
}
