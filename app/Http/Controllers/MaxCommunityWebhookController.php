<?php

namespace App\Http\Controllers;

use App\Models\CommunityIdentity;
use App\Models\CommunityLoginChallenge;
use App\Services\Community\MaxLoginPromptService;
use App\Services\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaxCommunityWebhookController extends Controller
{
    public function __construct(private readonly SiteSettingsService $siteSettings) {}

    public function __invoke(
        Request $request,
        MaxLoginPromptService $loginPrompt,
    ): JsonResponse {
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
            $payload = is_scalar($request->input('payload')) ? (string) $request->input('payload') : null;
            $challenge = $active ? $this->pendingChallenge($payload) : null;

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

            if ($active) {
                $loginPrompt->send(
                    (string) $userId,
                    $challenge,
                    $challenge !== null ? $payload : null,
                );
            }
        }

        return response()->json(['ok' => true]);
    }

    private function pendingChallenge(?string $payload): ?CommunityLoginChallenge
    {
        if ($payload === null || $payload === '' || strlen($payload) > 128) {
            return null;
        }

        $challenge = CommunityLoginChallenge::query()
            ->where('token_hash', hash('sha256', $payload))
            ->first();

        if ($challenge === null || $challenge->status !== 'pending') {
            return null;
        }

        if ($challenge->expires_at->isPast()) {
            $challenge->update(['status' => 'expired']);

            return null;
        }

        return $challenge;
    }
}
