<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_is_hidden_until_it_is_enabled_and_has_a_channel_url(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-telegram-popup', false);

        SiteSetting::instance()->update([
            'telegram_popup_enabled' => true,
            'telegram_popup_channel_url' => null,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/')->assertOk()->assertDontSee('data-telegram-popup', false);
    }

    public function test_popup_uses_editable_texts_link_and_delays(): void
    {
        SiteSetting::instance()->update([
            'telegram_popup_enabled' => true,
            'telegram_popup_badge' => 'Наш Telegram',
            'telegram_popup_title' => 'Тестовый заголовок',
            'telegram_popup_description' => 'Тестовое описание',
            'telegram_popup_button_text' => 'Перейти',
            'telegram_popup_dismiss_text' => 'Закрыть',
            'telegram_popup_channel_url' => 'https://t.me/example_channel',
            'telegram_popup_mobile_url' => 'tg://resolve?domain=example_channel',
            'telegram_popup_show_delay' => 7,
            'telegram_popup_auto_close_delay' => 19,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-telegram-popup', false)
            ->assertSee('data-show-delay="7"', false)
            ->assertSee('data-auto-close-delay="19"', false)
            ->assertSee('Тестовый заголовок')
            ->assertSee('Тестовое описание')
            ->assertSee('https://t.me/example_channel', false)
            ->assertSee('data-mobile-url="tg://resolve?domain=example_channel"', false);
    }
}
