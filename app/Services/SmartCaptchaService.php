<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SmartCaptchaService
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    public function enabled(string $form): bool
    {
        return match ($form) {
            'commercial_offer' => (bool) $this->settings->get()->smartcaptcha_commercial_offer_enabled,
            'contact' => (bool) $this->settings->get()->smartcaptcha_contact_enabled,
            default => false,
        };
    }

    public function validate(string $form, string $token, ?string $ip, string $host): void
    {
        if (! $this->enabled($form)) {
            return;
        }

        $settings = $this->settings->get();
        $secret = trim((string) $settings->smartcaptcha_server_key);
        $unavailable = 'Не удалось проверить капчу. Попробуйте ещё раз чуть позже.';

        if ($secret === '' || blank($settings->smartcaptcha_site_key)) {
            throw ValidationException::withMessages(['smart_token' => $unavailable]);
        }

        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(3)->timeout(8)
                ->post('https://smartcaptcha.cloud.yandex.ru/validate', [
                    'secret' => $secret,
                    'token' => $token,
                    'ip' => $ip,
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['smart_token' => $unavailable]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages(['smart_token' => $unavailable]);
        }

        $verifiedHost = $response->json('host');
        $verifiedHost = is_string($verifiedHost) && $verifiedHost !== ''
            ? parse_url('https://'.$verifiedHost, PHP_URL_HOST)
            : null;

        if ($response->json('status') !== 'ok' || ! is_string($verifiedHost)
            || strcasecmp($verifiedHost, $host) !== 0) {
            throw ValidationException::withMessages([
                'smart_token' => 'Проверка капчи не пройдена или устарела. Пройдите её ещё раз.',
            ]);
        }
    }
}
