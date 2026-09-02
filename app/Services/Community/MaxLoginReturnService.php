<?php

namespace App\Services\Community;

use App\Models\CommunityLoginChallenge;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final class MaxLoginReturnService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
        private readonly MaxApiClient $api,
    ) {}

    public function send(CommunityLoginChallenge $challenge, string $providerUserId): void
    {
        if ($challenge->return_sent_at !== null) {
            return;
        }

        $returnUrl = $this->url($challenge);

        $response = $this->api->request()
            ->acceptJson()
            ->withHeaders(['Authorization' => $this->settings->maxBotToken()])
            ->timeout(8)
            ->post('https://platform-api2.max.ru/messages?'.http_build_query(['user_id' => $providerUserId]), [
                'text' => 'Вход подтверждён. Нажмите кнопку, чтобы вернуться в сообщество 24Logist.',
                'disable_link_preview' => true,
                'attachments' => [[
                    'type' => 'inline_keyboard',
                    'payload' => [
                        'buttons' => [[[
                            'type' => 'link',
                            'text' => 'Вернуться в сообщество',
                            'url' => $returnUrl,
                        ]]],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('MAX не принял сообщение со ссылкой возврата.');
        }

        CommunityLoginChallenge::query()
            ->whereKey($challenge->getKey())
            ->whereNull('return_sent_at')
            ->update(['return_sent_at' => now()]);
    }

    public function url(CommunityLoginChallenge $challenge): string
    {
        return URL::temporarySignedRoute(
            'community.auth.max.complete',
            now()->addSeconds((int) config('community.max.return_link_ttl', 600)),
            ['challenge' => $challenge->getKey()],
        );
    }
}
