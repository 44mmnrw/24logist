@php
    $section = $landing->section('reviews');
    $reviews = $section
        ? \App\Support\LandingReviews::renderable($section->blocks)
        : collect();
@endphp

@if ($section && $reviews->isNotEmpty())
<section class="reviews-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif @if($section->title) aria-labelledby="reviews-title" @else aria-label="Отзывы клиентов" @endif>
    <div class="landing-shell">
        <header class="reviews-section__head">
            @if ($section->kicker)
                <span class="reviews-section__kicker">{{ $section->kicker }}</span>
            @endif
            @if ($section->title)
                <h2 id="reviews-title">{{ $section->title }}</h2>
            @endif
            @if ($section->description)
                <p>{{ $section->description }}</p>
            @endif
        </header>

        <div class="reviews-grid" role="list" tabindex="0" aria-label="Отзывы клиентов">
            @foreach ($reviews as $review)
                @php
                    $extra = is_array($review->extra) ? $review->extra : [];
                    $logoUrl = \App\Support\LandingMedia::url($extra['logo_path'] ?? null);
                    $metrics = \App\Support\LandingReviews::metrics($review);
                @endphp
                <article class="review-card" role="listitem">
                    <header class="review-card__company">
                        @if ($logoUrl)
                            <img class="review-card__logo" src="{{ $logoUrl }}" alt="Логотип {{ $review->title }}" loading="lazy" decoding="async">
                        @else
                            <span class="review-card__monogram" aria-hidden="true">{{ \App\Support\LandingReviews::monogram((string) $review->title) }}</span>
                        @endif
                        <div class="review-card__company-copy">
                            <h3>{{ $review->title }}</h3>
                            <div class="review-card__meta">
                                @if ($clientType = \App\Support\LandingReviews::clientTypeLabel($review))
                                    <span>{{ $clientType }}</span>
                                @endif
                                @if ($segment = \App\Support\LandingReviews::segmentLabel($review))
                                    <span>{{ $segment }}</span>
                                @endif
                                @if (! empty($extra['region']))
                                    <span>{{ $extra['region'] }}</span>
                                @endif
                            </div>
                        </div>
                    </header>

                    <blockquote class="review-card__quote">
                        <p>{{ $review->description }}</p>
                    </blockquote>

                    @if ($metrics !== [])
                        <dl @class(['review-card__metrics', 'review-card__metrics--single' => count($metrics) === 1])>
                            @foreach ($metrics as $metric)
                                <div>
                                    <dt>{{ $metric['label'] }}</dt>
                                    <dd>{{ $metric['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    <footer class="review-card__footer">
                        <div class="review-card__representative">
                            @if (! empty($extra['representative_name']))
                                <strong>{{ $extra['representative_name'] }}</strong>
                            @endif
                            @if (! empty($extra['representative_position']))
                                <span>{{ $extra['representative_position'] }}</span>
                            @endif
                        </div>
                        <span class="review-card__approved">
                            <svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true">
                                <path d="M10 1.75 12.1 3l2.45-.05.95 2.25 2 1.4-.5 2.4.8 2.3-1.75 1.7-.2 2.45-2.35.65L12 18l-2-.95L8 18l-1.5-1.9-2.35-.65-.2-2.45L2.2 11.3 3 9l-.5-2.4 2-1.4.95-2.25L7.9 3 10 1.75Z" fill="currentColor" opacity=".16"/>
                                <path d="m6.5 10 2.2 2.2 4.8-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Отзыв согласован с клиентом
                        </span>
                    </footer>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
