<?php

namespace App\Services\Community;

use App\Models\CommunityIdentity;
use App\Models\CommunityUser;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CommunityIdentityManager
{
    public function resolve(string $provider, string $providerUserId, ?CommunityUser $linkTo = null, bool $botAccess = false): CommunityUser
    {
        return DB::transaction(function () use ($provider, $providerUserId, $linkTo, $botAccess): CommunityUser {
            $identity = CommunityIdentity::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->lockForUpdate()
                ->first();

            if ($identity !== null) {
                if ($linkTo !== null && $identity->community_user_id !== $linkTo->id) {
                    throw ValidationException::withMessages([
                        'provider' => 'Этот внешний аккаунт уже связан с другим профилем.',
                    ]);
                }

                $identity->update([
                    'bot_access' => $identity->bot_access || $botAccess,
                    'last_verified_at' => now(),
                ]);

                return $identity->user()->withTrashed()->firstOrFail();
            }

            $user = $linkTo ?? CommunityUser::query()->create([
                'username' => $this->placeholderUsername($provider),
            ]);

            try {
                $user->identities()->create([
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                    'bot_access' => $botAccess,
                    'bot_status' => $botAccess ? 'active' : 'unknown',
                    'last_verified_at' => now(),
                ]);
            } catch (QueryException) {
                throw ValidationException::withMessages([
                    'provider' => 'Не удалось связать внешний аккаунт. Повторите вход.',
                ]);
            }

            return $user;
        });
    }

    private function placeholderUsername(string $provider): string
    {
        do {
            $username = $provider.'-'.Str::lower(Str::random(12));
        } while (CommunityUser::withTrashed()->where('username', $username)->exists());

        return $username;
    }
}
