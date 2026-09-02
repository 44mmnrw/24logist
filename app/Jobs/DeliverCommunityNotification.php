<?php

namespace App\Jobs;

use App\Models\CommunityNotificationDelivery;
use App\Services\SiteSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverCommunityNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public int $deliveryId) {}

    public function handle(SiteSettingsService $settings): void
    {
        $delivery = CommunityNotificationDelivery::query()
            ->with(['notification', 'identity'])
            ->find($this->deliveryId);

        if ($delivery === null || $delivery->status === 'sent') {
            return;
        }

        $delivery->increment('attempts');
        $identity = $delivery->identity;
        $notification = $delivery->notification;

        if ($identity === null || $notification === null || ! $identity->notifications_enabled || ! $identity->bot_access) {
            $delivery->update(['status' => 'cancelled']);

            return;
        }

        $text = trim((string) ($notification->data['message'] ?? 'Новое уведомление в сообществе 24Logist'));
        $url = (string) ($notification->data['url'] ?? route('community.notifications'));
        $message = mb_substr($text, 0, 300)."\n".$url;

        try {
            $response = match ($delivery->provider) {
                'telegram' => Http::timeout(8)->post(
                    'https://api.telegram.org/bot'.$settings->telegramBotToken().'/sendMessage',
                    ['chat_id' => $identity->provider_user_id, 'text' => $message, 'disable_web_page_preview' => true],
                ),
                'max' => Http::timeout(8)
                    ->withHeaders(['Authorization' => $settings->maxBotToken()])
                    ->post('https://platform-api2.max.ru/messages?'.http_build_query(['user_id' => $identity->provider_user_id]), [
                        'text' => $message,
                        'disable_link_preview' => false,
                    ]),
                default => null,
            };

            if ($response === null) {
                $delivery->update(['status' => 'cancelled', 'last_error' => 'Unknown provider']);

                return;
            }

            if ($response->successful()) {
                $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);

                return;
            }

            if (in_array($response->status(), [400, 403, 404], true)) {
                $identity->update(['bot_access' => false, 'notifications_enabled' => false, 'bot_status' => 'stopped']);
                $delivery->update(['status' => 'failed', 'last_error' => 'Provider rejected delivery ('.$response->status().')']);

                return;
            }

            throw new \RuntimeException('Provider error '.$response->status());
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'retrying', 'last_error' => mb_substr($exception->getMessage(), 0, 1000)]);
            throw $exception;
        }
    }
}
