<?php

namespace App\Http\Controllers;

use App\Services\Community\CommunityIdentityManager;
use App\Services\Community\CommunityAvatarService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VkCommunityAuthController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->settings->communityVkEnabled(), 404);
        abort_if(
            blank($this->settings->vkClientId()) || blank($this->settings->vkServiceToken()),
            503,
            'VK ID ещё не настроен: укажите ID приложения и сервисный ключ доступа.',
        );

        $state = Str::random(64);
        $verifier = Str::random(96);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $redirectUri = $this->settings->vkRedirectUri() ?: route('community.auth.vk.callback');

        $request->session()->put('community.vk', [
            'state' => $state,
            'verifier' => $verifier,
            'link' => auth('community')->check(),
        ]);

        return redirect()->away('https://id.vk.ru/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->settings->vkClientId(),
            'app_id' => $this->settings->vkClientId(),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 's256',
            'prompt' => '',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(
        Request $request,
        CommunityIdentityManager $identities,
        CommunityAvatarService $avatars,
    ): RedirectResponse
    {
        abort_unless($this->settings->communityVkEnabled(), 404);

        $flow = $request->session()->pull('community.vk');

        if (! is_array($flow) || ! hash_equals((string) ($flow['state'] ?? ''), (string) $request->query('state'))) {
            throw ValidationException::withMessages(['vk' => 'Сессия входа VK ID недействительна.']);
        }

        if ($request->filled('error')) {
            throw ValidationException::withMessages(['vk' => 'VK ID отклонил вход. Попробуйте ещё раз.']);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:4096'],
            'device_id' => ['required', 'string', 'max:255'],
        ]);
        $state = (string) $flow['state'];
        $redirectUri = $this->settings->vkRedirectUri() ?: route('community.auth.vk.callback');
        $tokenRequest = [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'client_id' => $this->settings->vkClientId(),
            'code_verifier' => (string) $flow['verifier'],
            'state' => $state,
            'device_id' => $data['device_id'],
            'code' => $data['code'],
        ];

        $tokenRequest['service_token'] = $this->settings->vkServiceToken();

        $response = Http::asForm()->timeout(8)->post('https://id.vk.ru/oauth2/auth', $tokenRequest);
        $responseState = (string) $response->json('state');
        $providerUserId = $response->json('user_id');

        if (! $response->successful()
            || $responseState === ''
            || ! hash_equals($state, $responseState)
            || ! is_scalar($providerUserId)
            || (string) $providerUserId === '') {
            throw ValidationException::withMessages(['vk' => 'VK ID не подтвердил вход. Попробуйте ещё раз.']);
        }

        $linkTo = ($flow['link'] ?? false) ? auth('community')->user() : null;
        $user = $identities->resolve('vk', (string) $providerUserId, $linkTo);
        $avatarUrl = $this->vkAvatarUrl($response->json('access_token'), (string) $providerUserId);
        $avatars->syncFromProvider($user, 'vk', $avatarUrl);
        auth('community')->login($user, true);
        $request->session()->regenerate();

        $message = $linkTo ? 'VK ID привязан к профилю.' : 'Вход через VK ID выполнен.';

        return $user->isOnboarded()
            ? redirect()->intended(route('community.index'))->with('status', $message)
            : redirect()->route('community.onboarding')->with('status', $message);
    }

    private function vkAvatarUrl(mixed $accessToken, string $providerUserId): ?string
    {
        if (! is_string($accessToken) || $accessToken === '') {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://id.vk.ru/oauth2/user_info', [
                'client_id' => $this->settings->vkClientId(),
                'access_token' => $accessToken,
            ]);
            $userId = $response->json('user.user_id');
            $avatar = $response->json('user.avatar');

            return $response->successful()
                && is_scalar($userId)
                && hash_equals($providerUserId, (string) $userId)
                && is_string($avatar)
                ? $avatar
                : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
