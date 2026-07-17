@php
    $settings = app(\App\Services\SiteSettingsService::class)->get();
    $enabled = (bool) $settings->telegram_popup_enabled && filled($settings->telegram_popup_channel_url);
@endphp

@if ($enabled)
    <div
        class="telegram-popup"
        data-telegram-popup
        data-show-delay="{{ max(0, (int) $settings->telegram_popup_show_delay) }}"
        data-auto-close-delay="{{ max(0, (int) $settings->telegram_popup_auto_close_delay) }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="telegram-popup-title"
        aria-describedby="telegram-popup-description"
        aria-hidden="true"
        hidden
    >
        <div class="telegram-popup__card" role="document">
            <button class="telegram-popup__close" type="button" data-telegram-popup-close aria-label="Закрыть">×</button>
            <div class="telegram-popup__eyebrow">
                <x-landing.icon name="icon:telegram" class="telegram-popup__logo" />
                <span>{{ $settings->telegram_popup_badge }}</span>
            </div>
            <h2 id="telegram-popup-title">{{ $settings->telegram_popup_title }}</h2>
            <p id="telegram-popup-description">{{ $settings->telegram_popup_description }}</p>
            <a
                class="telegram-popup__subscribe"
                href="{{ $settings->telegram_popup_channel_url }}"
                data-mobile-url="{{ $settings->telegram_popup_mobile_url }}"
                target="_blank"
                rel="noopener noreferrer"
                data-telegram-popup-subscribe
            >
                <span>{{ $settings->telegram_popup_button_text }}</span>
            </a>
            <button class="telegram-popup__later" type="button" data-telegram-popup-close>{{ $settings->telegram_popup_dismiss_text }}</button>
        </div>
    </div>
@endif
