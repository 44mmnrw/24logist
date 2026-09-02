<?php

namespace App\Http\Controllers;

use App\Models\CommunityIdentity;
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
                $loginPrompt->send((string) $userId);
            }
        }

        return response()->json(['ok' => true]);
    }
}
