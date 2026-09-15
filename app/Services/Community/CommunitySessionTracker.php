<?php

namespace App\Services\Community;

use App\Models\CommunityIdentity;
use App\Models\CommunityUser;
use App\Models\CommunityUserSession;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

final class CommunitySessionTracker
{
    public function login(Request $request, CommunityUser $user, string $provider): void
    {
        $identity = $user->identities()->where('provider', $provider)->first();
        $now = now();
        $session = $this->upsert($request, $user, $identity, $provider, $now);

        $user->forceFill([
            'last_login_at' => $now,
            'last_seen_at' => $now,
            'last_login_ip' => $request->ip(),
            'last_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ])->save();

        CommunityUserSession::query()
            ->where('community_user_id', $user->id)
            ->where('id', '!=', $session->id)
            ->whereNull('logged_out_at')
            ->where('expires_at', '<', $now)
            ->update(['logged_out_at' => $now]);
    }

    public function touch(Request $request, CommunityUser $user): void
    {
        $session = CommunityUserSession::query()
            ->where('session_id_hash', $this->sessionHash($request))
            ->where('community_user_id', $user->id)
            ->first();

        if ($session?->last_seen_at?->gt(now()->subMinutes(5)) === true) {
            return;
        }

        $now = now();
        $this->upsert($request, $user, null, null, $now);
        $user->forceFill([
            'last_seen_at' => $now,
            'last_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
        ])->save();
    }

    public function logout(Request $request, CommunityUser $user): void
    {
        CommunityUserSession::query()
            ->where('session_id_hash', $this->sessionHash($request))
            ->where('community_user_id', $user->id)
            ->whereNull('logged_out_at')
            ->update(['last_seen_at' => now(), 'logged_out_at' => now()]);
    }

    private function upsert(
        Request $request,
        CommunityUser $user,
        ?CommunityIdentity $identity,
        ?string $provider,
        CarbonInterface $now,
    ): CommunityUserSession {
        $hash = $this->sessionHash($request);
        $existing = CommunityUserSession::query()->where('session_id_hash', $hash)->first();

        return CommunityUserSession::query()->updateOrCreate(
            ['session_id_hash' => $hash],
            [
                'community_user_id' => $user->id,
                'community_identity_id' => $identity?->id ?? $existing?->community_identity_id,
                'provider' => $provider ?? $existing?->provider,
                'ip_address' => $provider !== null
                    ? $request->ip()
                    : ($existing?->ip_address ?? $request->ip()),
                'user_agent' => $provider !== null
                    ? mb_substr((string) $request->userAgent(), 0, 2000)
                    : ($existing?->user_agent ?? mb_substr((string) $request->userAgent(), 0, 2000)),
                'logged_in_at' => $existing?->logged_in_at ?? $now,
                'last_seen_at' => $now,
                'expires_at' => $now->copy()->addMinutes((int) config('session.lifetime', 120)),
                'logged_out_at' => null,
            ],
        );
    }

    private function sessionHash(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }
}
