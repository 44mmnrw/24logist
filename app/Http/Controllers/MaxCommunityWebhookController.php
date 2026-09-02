<?php

namespace App\Http\Controllers;

use App\Models\CommunityIdentity;
use App\Models\CommunityLoginChallenge;
use App\Models\CommunityUser;
use App\Services\Community\CommunityIdentityManager;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaxCommunityWebhookController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettings) {}

    public function __invoke(Request $request, CommunityIdentityManager $identities): JsonResponse
    {
        abort_unless($this->siteSettings->communityMaxEnabled(), 404);
        $secret = $this->siteSettings->maxWebhookSecret();
        $provided = (string) $request->header('X-Max-Bot-Api-Secret', '');
        abort_if($secret === '' || ! hash_equals($secret, $provided), 403);

        $type = (string) ($request->input('update_type') ?: $request->input('type'));
        $userId = $request->input('user.user_id')
            ?? $request->input('user.id')
            ?? $request->input('user_id');

        if (is_scalar($userId) && in_array($type, ['bot_started', 'bot_stopped', 'dialog_removed'], true)) {
            $active = $type === 'bot_started';

            if ($active && is_scalar($request->input('payload'))) {
                $this->approveLoginChallenge((string) $request->input('payload'), (string) $userId, $identities);
            }

            $identity = CommunityIdentity::query()
                ->where('provider', 'max')
                ->where('provider_user_id', (string) $userId)
                ->first();

            $identity?->update([
                'bot_access' => $active,
                'notifications_enabled' => $active ? $identity->notifications_enabled : false,
                'bot_status' => $active ? 'active' : 'stopped',
                'last_verified_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function approveLoginChallenge(
        string $payload,
        string $providerUserId,
        CommunityIdentityManager $identities,
    ): void {
        if ($payload === '' || strlen($payload) > 128) {
            return;
        }

        DB::transaction(function () use ($payload, $providerUserId, $identities): void {
            $challenge = CommunityLoginChallenge::query()
                ->where('token_hash', hash('sha256', $payload))
                ->lockForUpdate()
                ->first();

            if ($challenge === null || $challenge->status !== 'pending') {
                return;
            }

            if ($challenge->expires_at->isPast()) {
                $challenge->update(['status' => 'expired']);

                return;
            }

            $linkTo = $challenge->link_to_user_id
                ? CommunityUser::query()->find($challenge->link_to_user_id)
                : null;

            try {
                $user = $identities->resolve('max', $providerUserId, $linkTo, true);
            } catch (ValidationException) {
                $challenge->update(['status' => 'failed']);

                return;
            }

            if ($user->trashed()) {
                $challenge->update(['status' => 'failed']);

                return;
            }

            $user->identities()->where('provider', 'max')->update([
                'bot_access' => true,
                'bot_status' => 'active',
                'last_verified_at' => now(),
            ]);
            $challenge->update([
                'community_user_id' => $user->id,
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        }, 3);
    }
}
