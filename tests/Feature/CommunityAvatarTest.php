<?php

namespace Tests\Feature;

use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\Community\CommunityAvatarService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityAvatarTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_provider_avatar_is_copied_and_custom_avatar_is_not_overwritten(): void
    {
        $user = CommunityUser::factory()->create();
        $png = base64_decode(self::PNG, true);
        Http::fake(['https://images.example.test/avatar.png' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);

        $avatars = app(CommunityAvatarService::class);
        $avatars->syncFromProvider($user, 'telegram', 'https://images.example.test/avatar.png');
        $user->refresh();

        $this->assertSame('telegram', $user->avatar_source);
        Storage::disk('public')->assertExists($user->avatar_path);

        $custom = UploadedFile::fake()->createWithContent('my-avatar.png', $png);
        $this->assertTrue($avatars->storeUpload($user, $custom));
        $customPath = $user->fresh()->avatar_path;
        $this->assertSame('custom', $user->fresh()->avatar_source);

        $avatars->syncFromProvider($user->fresh(), 'telegram', 'https://images.example.test/avatar.png');
        $this->assertSame($customPath, $user->fresh()->avatar_path);
        Storage::disk('public')->assertExists($customPath);
    }

    public function test_user_can_upload_and_remove_avatar_in_profile_settings(): void
    {
        $user = CommunityUser::factory()->create();
        $png = base64_decode(self::PNG, true);

        $this->actingAs($user, 'community')->put(route('community.settings.update'), [
            'avatar' => UploadedFile::fake()->createWithContent('avatar.png', $png),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('custom', $user->avatar_source);
        Storage::disk('public')->assertExists($user->avatar_path);

        $oldPath = $user->avatar_path;
        $this->actingAs($user, 'community')->put(route('community.settings.update'), [
            'remove_avatar' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_user_can_update_public_profile_without_changing_id(): void
    {
        $user = CommunityUser::factory()->create([
            'username' => 'stable_id',
            'display_name' => 'Старый никнейм',
        ]);

        $this->actingAs($user, 'community')->get(route('community.settings'))
            ->assertOk()
            ->assertSee('name="display_name"', false)
            ->assertSee('name="transport_role"', false)
            ->assertSee('name="bio"', false)
            ->assertSee('@stable_id');

        $this->actingAs($user, 'community')->put(route('community.settings.update'), [
            'username' => 'attempted_change',
            'role' => 'moderator',
            'display_name' => 'Диспетчер Юрий',
            'transport_role' => 'dispatcher',
            'bio' => "Организую перевозки по России.\nОпыт — 8 лет.",
        ])->assertRedirect()->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('stable_id', $user->username);
        $this->assertSame('user', $user->role);
        $this->assertSame('Диспетчер Юрий', $user->display_name);
        $this->assertSame('dispatcher', $user->transport_role);

        $this->get(route('community.profile', $user))
            ->assertOk()
            ->assertSee('Диспетчер Юрий')
            ->assertSee('@stable_id')
            ->assertSee('Диспетчер')
            ->assertSee('Организую перевозки по России.');
    }
}
