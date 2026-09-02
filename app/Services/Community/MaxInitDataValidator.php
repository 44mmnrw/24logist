<?php

namespace App\Services\Community;

use App\Services\SiteSettingsService;
use Illuminate\Validation\ValidationException;

final class MaxInitDataValidator
{
    public function __construct(private readonly SiteSettingsService $settings) {}

    /** @return array{user: array{id: int|string}, auth_date: int, query_id: string} */
    public function validate(string $initData): array
    {
        $pairs = explode('&', $initData);
        $data = [];

        foreach ($pairs as $pair) {
            if ($pair === '' || ! str_contains($pair, '=')) {
                throw $this->invalid();
            }

            [$key, $value] = explode('=', $pair, 2);
            $key = rawurldecode($key);

            if ($key === '' || array_key_exists($key, $data)) {
                throw $this->invalid();
            }

            $data[$key] = rawurldecode($value);
        }

        $hash = $data['hash'] ?? null;
        unset($data['hash']);
        ksort($data, SORT_STRING);

        $botToken = $this->settings->maxBotToken();

        if (! is_string($hash) || $hash === '' || $botToken === '') {
            throw $this->invalid();
        }

        $checkString = implode("\n", array_map(
            static fn (string $key, string $value): string => $key.'='.$value,
            array_keys($data),
            array_values($data),
        ));
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $expected = hash_hmac('sha256', $checkString, $secretKey);

        if (! hash_equals($expected, strtolower($hash))) {
            throw $this->invalid();
        }

        $authDate = filter_var($data['auth_date'] ?? null, FILTER_VALIDATE_INT);
        $ttl = (int) config('community.max.init_data_ttl', 3600);

        if ($authDate === false || $authDate > time() + 30 || $authDate < time() - $ttl) {
            throw ValidationException::withMessages(['max' => 'Данные MAX устарели. Откройте мини-приложение заново.']);
        }

        $user = json_decode($data['user'] ?? '', true);
        $queryId = $data['query_id'] ?? null;

        if (! is_array($user)
            || ! isset($user['id'])
            || ! is_scalar($user['id'])
            || ! is_string($queryId)
            || $queryId === ''
            || strlen($queryId) > 512) {
            throw $this->invalid();
        }

        return ['user' => $user, 'auth_date' => (int) $authDate] + $data;
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['max' => 'Не удалось подтвердить данные MAX.']);
    }
}
