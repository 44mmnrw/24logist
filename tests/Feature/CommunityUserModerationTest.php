<?php

namespace Tests\Feature;

use App\Models\CommunityIdentity;
use App\Models\CommunityModerationAction;
use App\Models\CommunityUser;
use App\Models\CommunityUserSession;
use App\Models\User;
use App\Services\Community\CommunityUserModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommunityUserModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_the_community_participants_moderation_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.community-users.index'))
            ->assertOk()
            ->assertSee('Участники сообщества');
    }

    public function test_admin_can_open_full_participant_metadata_page(): void
    {
        $admin = User::factory()->create();
        $user = CommunityUser::factory()->create([
            'last_login_ip' => '203.0.113.15',
            'last_login_at' => now(),
            'last_seen_at' => now(),
            'last_user_agent' => 'Metadata browser',
        ]);
        $identity = CommunityIdentity::query()->create([
            'community_user_id' => $user->id,
            'provider' => 'telegram',
            'provider_user_id' => '123456789',
            'last_verified_at' => now(),
        ]);
        CommunityUserSession::query()->create([
            'community_user_id' => $user->id,
            'community_identity_id' => $identity->id,
            'session_id_hash' => hash('sha256', 'admin-metadata-test'),
            'provider' => 'telegram',
            'ip_address' => '203.0.113.15',
            'user_agent' => 'Metadata browser',
            'logged_in_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('filament.admin.resources.community-users.edit', $user))
            ->assertOk()
            ->assertSee('203.0.113.15')
            ->assertSee('Metadata browser')
            ->assertSee('User-Agent')
            ->assertSeeText('Привязанные соцсети и способы входа')
            ->assertSeeText('Сессии и история активности')
            ->assertSeeText('Темы участника')
            ->assertSeeText('Комментарии участника');
    }

    public function test_admin_can_warn_a_user_and_the_user_receives_the_warning(): void
    {
        $admin = User::factory()->create();
        $user = CommunityUser::factory()->create();

        app(CommunityUserModerationService::class)->moderate(
            $user,
            CommunityUserModerationService::ACTION_WARN,
            $admin->id,
            'Не публикуйте повторяющиеся рекламные сообщения.',
        );

        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'user',
            'target_id' => $user->id,
            'action' => 'warn',
            'reason' => 'Не публикуйте повторяющиеся рекламные сообщения.',
        ]);
        $this->assertSame(1, $user->warnings()->count());
        $this->assertSame(
            'Предупреждение модератора: Не публикуйте повторяющиеся рекламные сообщения.',
            $user->communityNotifications()->firstOrFail()->data['message'],
        );
    }

    public function test_admin_can_temporarily_suspend_ban_and_unrestrict_a_user(): void
    {
        $admin = User::factory()->create();
        $user = CommunityUser::factory()->create();
        $service = app(CommunityUserModerationService::class);

        $service->moderate(
            $user,
            CommunityUserModerationService::ACTION_SUSPEND,
            $admin->id,
            'Оскорбления участников.',
            7,
        );

        $user->refresh();
        $this->assertTrue($user->isRestricted());
        $this->assertNotNull($user->suspended_until);
        $this->assertNull($user->banned_at);
        $this->assertSame(7, CommunityModerationAction::query()->latest('id')->firstOrFail()->metadata['duration_days']);

        $service->moderate(
            $user,
            CommunityUserModerationService::ACTION_BAN,
            $admin->id,
            'Повторное нарушение правил.',
        );

        $user->refresh();
        $this->assertTrue($user->isRestricted());
        $this->assertNotNull($user->banned_at);
        $this->assertNull($user->suspended_until);

        $service->moderate(
            $user,
            CommunityUserModerationService::ACTION_UNRESTRICT,
            $admin->id,
        );

        $user->refresh();
        $this->assertFalse($user->isRestricted());
        $this->assertNull($user->banned_at);
        $this->assertNull($user->suspended_until);
    }

    public function test_admin_can_soft_delete_and_restore_a_user_without_losing_the_audit_log(): void
    {
        $admin = User::factory()->create();
        $user = CommunityUser::factory()->create();
        $service = app(CommunityUserModerationService::class);

        $service->moderate(
            $user,
            CommunityUserModerationService::ACTION_DELETE,
            $admin->id,
            'Аккаунт создан для спама.',
        );

        $this->assertSoftDeleted('community_users', ['id' => $user->id]);
        $this->assertDatabaseHas('community_moderation_actions', [
            'target_type' => 'user',
            'target_id' => $user->id,
            'action' => 'delete_user',
        ]);

        $service->moderate(
            CommunityUser::withTrashed()->findOrFail($user->id),
            CommunityUserModerationService::ACTION_RESTORE,
            $admin->id,
        );

        $this->assertNotSoftDeleted('community_users', ['id' => $user->id]);
        $this->assertSame(2, CommunityModerationAction::query()->where('target_type', 'user')->where('target_id', $user->id)->count());
    }

    public function test_disciplinary_actions_require_a_reason_and_valid_duration(): void
    {
        $user = CommunityUser::factory()->create();
        $service = app(CommunityUserModerationService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->moderate(
            $user,
            CommunityUserModerationService::ACTION_SUSPEND,
            null,
            '',
            0,
        );
    }
}
