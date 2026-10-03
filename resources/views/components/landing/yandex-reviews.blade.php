@php
    $widgetCode = array_key_exists('yandex_reviews_code', $extra)
        ? $extra['yandex_reviews_code']
        : \App\Support\YandexRatingWidget::DEFAULT_CODE;
    $organizationId = \App\Support\YandexRatingWidget::organizationId($widgetCode);
    $reviewsTitle = $extra['yandex_reviews_title'] ?? \App\Support\YandexRatingWidget::DEFAULT_TITLE;
    $reviewsText = $extra['yandex_reviews_text'] ?? \App\Support\YandexRatingWidget::DEFAULT_TEXT;
@endphp

@if (($extra['yandex_reviews_enabled'] ?? true) && $organizationId)
    <div class="landing-shell">
    <aside class="yandex-reviews" aria-label="Отзывы на Яндексе">
        <div class="yandex-reviews__copy">
            <span class="yandex-reviews__eyebrow">Обратная связь</span>
            @if ($reviewsTitle)
                <h3>{{ $reviewsTitle }}</h3>
            @endif
            @if ($reviewsText)
                <p>{{ $reviewsText }}</p>
            @endif
        </div>
        <div class="yandex-reviews__rating">
            <div class="yandex-reviews__widget">
            <iframe
                src="https://yandex.ru/sprav/widget/rating-badge/{{ $organizationId }}?type=rating"
                width="150"
                height="50"
                title="Рейтинг логистРу на Яндексе"
                loading="lazy"
            ></iframe>
            </div>
            <a href="https://yandex.ru/profile/{{ $organizationId }}?intent=reviews" target="_blank" rel="noopener noreferrer">
                Читать отзывы <span aria-hidden="true">↗</span>
            </a>
        </div>
    </aside>
    </div>
@endif
