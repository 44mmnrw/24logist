<?php

namespace App\Http\Controllers;

use App\Services\Community\CommunityAvatarService;
use App\Services\Community\CommunityIdentityManager;
use App\Services\Community\TelegramIdTokenVerifier;
use App\Services\SiteSettingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TelegramCommunityAuthController extends Controller
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->settings->communityTelegramEnabled(), 404);
        $state = Str::random(64);
        $nonce = Str::random(64);
        $verifier = Str::random(96);
        $notify = $request->boolean('notify');
        $request->session()->put('community.telegram', [
            'state' => $state,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'notify' => $notify,
            'link' => auth('community')->check(),
        ]);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $redirectUri = $this->settings->telegramRedirectUri() ?: route('community.auth.telegram.callback');
        $scope = 'openid profile'.($notify ? ' telegram:bot_access' : '');

        return redirect()->away('https://oauth.telegram.org/auth?'.http_build_query([
            'client_id' => $this->settings->telegramClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(
        Request $request,
        TelegramIdTokenVerifier $verifier,
        CommunityIdentityManager $identities,
        CommunityAvatarService $avatars,
    ): RedirectResponse {
        $flow = $request->session()->pull('community.telegram');
        $state = $request->query('state');

        if (! is_array($flow) || ! is_string($state) || ! hash_equals((string) ($flow['state'] ?? ''), $state)) {
            throw ValidationException::withMessages(['telegram' => 'Сессия входа Telegram недействительна.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:4096']]);
        $redirectUri = $this->settings->telegramRedirectUri() ?: route('community.auth.telegram.callback');
        try {
            $response = Http::asForm()
                ->withBasicAuth($this->settings->telegramClientId(), $this->settings->telegramClientSecret())
                ->timeout(8)
                ->post('https://oauth.telegram.org/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $request->query('code'),
                    'redirect_uri' => $redirectUri,
                    'client_id' => $this->settings->telegramClientId(),
                    'code_verifier' => $flow['verifier'],
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['telegram' => 'Telegram сейчас недоступен. Повторите вход позже.']);
        }

        if (! $response->successful() || blank($response->json('id_token'))) {
            throw ValidationException::withMessages(['telegram' => 'Telegram не подтвердил вход. Попробуйте ещё раз.']);
        }

        try {
            $claims = $verifier->verify((string) $response->json('id_token'), (string) $flow['nonce']);
        } catch (ValidationException $e) {
            return redirect()->route('community.login')->withErrors($e->errors());
        }
        $linkTo = ($flow['link'] ?? false) ? auth('community')->user() : null;
        $user = $identities->resolve('telegram', (string) $claims['sub'], $linkTo, (bool) ($flow['notify'] ?? false));
        $avatars->syncFromProvider($user, 'telegram', is_string($claims['picture'] ?? null) ? $claims['picture'] : null);
        auth('community')->login($user, true);
        $request->session()->regenerate();

        $message = $linkTo ? 'Telegram привязан к профилю.' : 'Вход через Telegram выполнен.';

        return $user->isOnboarded()
            ? redirect()->intended(route('community.index'))->with('status', $message)
            : redirect()->route('community.onboarding')->with('status', $message);
    }
}
