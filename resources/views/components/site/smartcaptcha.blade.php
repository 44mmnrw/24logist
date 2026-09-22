@props(['form'])

@if (app(\App\Services\SmartCaptchaService::class)->enabled($form))
    <div class="site-smartcaptcha" data-smartcaptcha data-sitekey="{{ app(\App\Services\SiteSettingsService::class)->get()->smartcaptcha_site_key }}">
        <div class="site-smartcaptcha__widget" data-smartcaptcha-widget></div>
        <p class="site-smartcaptcha__status" data-smartcaptcha-status role="status" hidden></p>
        <button type="button" class="site-smartcaptcha__retry" data-smartcaptcha-retry hidden>Повторить загрузку капчи</button>
    </div>
@endif
