<?php

namespace App\Services\Community;

use App\Services\SiteSettingsService;
use RuntimeException;

final class MaxLoginPromptService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly MaxApiClient $api,
    ) {}

    public function send(string $providerUserId, ?string $challengeToken = null): void
    {
        $button = [
            'type' => 'open_app',
            'text' => 'Авторизоваться',
            'web_app' => ltrim($this->settings->maxBotUsername(), '@'),
            'payload' => $challengeToken ?: 'community-login',
        ];

        $response = $this->api->request()
            ->acceptJson()
            ->withHeaders(['Authorization' => $this->settings->maxBotToken()])
            ->timeout(8)
            ->post('https://platform-api2.max.ru/messages?'.http_build_query(['user_id' => $providerUserId]), [
                'text' => 'Авторизуйтесь через MAX, чтобы вернуться в сообщество 24Logist.',
                'attachments' => [[
                    'type' => 'inline_keyboard',
                    'payload' => ['buttons' => [[$button]]],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('MAX не принял сообщение с кнопкой авторизации.');
        }
    }
}
