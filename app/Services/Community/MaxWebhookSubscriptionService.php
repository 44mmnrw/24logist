<?php

namespace App\Services\Community;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MaxWebhookSubscriptionService
{
    private const ENDPOINT = 'https://platform-api2.max.ru/subscriptions';

    public function __construct(private readonly SiteSettingsService $settings) {}

    public function register(): string
    {
        $this->settings->clearCache();
        $token = $this->settings->maxBotToken();
        $secret = $this->settings->maxWebhookSecret();
        $url = rtrim((string) config('app.url'), '/')
            .'/'.ltrim(route('community.webhooks.max', absolute: false), '/');

        if ($token === '') {
            throw new RuntimeException('Сначала сохраните Bot Token.');
        }

        if (preg_match('/^[A-Za-z0-9_-]{5,256}$/', $secret) !== 1) {
            throw new RuntimeException('Сохраните Webhook Secret длиной 5–256 символов: латиница, цифры, _ или -.');
        }

        if (! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Webhook MAX должен использовать публичный HTTPS-адрес сайта.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Authorization' => $token])
                ->timeout(10)
                ->post(self::ENDPOINT, [
                    'url' => $url,
                    'update_types' => ['bot_started', 'bot_stopped', 'dialog_removed'],
                    'secret' => $secret,
                ]);
        } catch (\Throwable) {
            throw new RuntimeException('MAX не ответил на запрос регистрации webhook. Попробуйте ещё раз.');
        }

        $message = trim((string) $response->json('message'));

        if (! $response->successful() || $response->json('success') !== true) {
            throw new RuntimeException($message !== '' ? $message : 'MAX отклонил регистрацию webhook. Проверьте Bot Token и настройки бота.');
        }

        return $message !== '' ? $message : 'Webhook MAX зарегистрирован.';
    }
}
