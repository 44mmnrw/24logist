@php
    $section = $landing->section('hero');
@endphp

@if ($section)
<section class="landing-hero" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $extra = $section?->extra ?? [];
        $bullets = $landing->blocks('hero', 'bullet');
        $carouselSlides = \App\Support\LandingHeroCarousel::slides($section);
        $carouselDelayMs = \App\Support\LandingHeroCarousel::delayMs($section);
        $fontSize = static fn (mixed $value, int $default): int => is_numeric($value)
            ? max(12, min(96, (int) $value))
            : $default;
        $titleFontSize = $fontSize($extra['title_font_size'] ?? null, 56);
        $subtitle1FontSize = $fontSize($extra['subtitle_1_font_size'] ?? null, 40);
        $subtitle2FontSize = $fontSize($extra['subtitle_2_font_size'] ?? null, 28);
    @endphp

    <div
        class="landing-shell landing-hero__shell"
        style="--hero-title-font-size: {{ $titleFontSize }}px; --hero-subtitle-1-font-size: {{ $subtitle1FontSize }}px; --hero-subtitle-2-font-size: {{ $subtitle2FontSize }}px;"
    >
        <div class="landing-hero__content">
            @if ($section?->badge_text)
                <div class="landing-badge">
                    @if ($section->badge_icon)
                        <x-landing.icon :name="$section->badge_icon" />
                    @endif
                    <span>{{ $section->badge_text }}</span>
                </div>
            @endif

            @if ($section?->seo_h1)
                <h1 class="landing-hero__seo-h1">{{ $section->seo_h1 }}</h1>
            @endif

            @if ($section?->title)
                @if ($section->seo_h1)
                    <h2 class="landing-hero__title">{{ $section->title }}</h2>
                @else
                    <h1 class="landing-hero__title">{{ $section->title }}</h1>
                @endif
            @endif

            @if ($section?->subtitle)
                <p class="landing-hero__subtitle">{{ $section->subtitle }}</p>
            @endif

            @if ($section?->description)
                <p class="landing-hero__subtitle-2">{{ $section->description }}</p>
            @endif

            <ul class="hero-list hero-list--progress">
                @foreach ($bullets as $bullet)
                    <li>
                        @if ($bullet->icon)
                            <span class="hero-list__icon" aria-hidden="true">
                                <x-landing.icon :name="$bullet->icon" />
                            </span>
                        @endif
                        {{ $bullet->title }}
                    </li>
                @endforeach
            </ul>

            <div class="landing-hero__actions">
                @if ($section?->button_primary_text)
                    @if (filled($section->button_primary_url))
                        <a href="{{ $section->button_primary_url }}" class="btn btn--primary">
                            {{ $section->button_primary_text }}
                            @if (! empty($extra['primary_button_icon']))
                                <x-landing.icon :name="$extra['primary_button_icon']" />
                            @endif
                        </a>
                    @else
                        <button type="button" class="btn btn--primary">
                            {{ $section->button_primary_text }}
                            @if (! empty($extra['primary_button_icon']))
                                <x-landing.icon :name="$extra['primary_button_icon']" />
                            @endif
                        </button>
                    @endif
                @endif
                @if ($section?->button_secondary_text)
                    @if (filled($section->button_secondary_url))
                        <a href="{{ $section->button_secondary_url }}" class="btn btn--ghost">{{ $section->button_secondary_text }}</a>
                    @else
                        <button type="button" class="btn btn--ghost">{{ $section->button_secondary_text }}</button>
                    @endif
                @endif
            </div>

            @if (! empty($extra['hint_text']))
                <div class="hero-hint">
                    @if (! empty($extra['hint_icon']))
                        <x-landing.icon :name="$extra['hint_icon']" />
                    @endif
                    <span>{{ $extra['hint_text'] }}</span>
                </div>
            @endif
        </div>

        @if ($carouselSlides !== [])
            <figure
                class="dashboard-card hero-carousel"
                data-hero-carousel
                data-delay="{{ $carouselDelayMs }}"
            >
                <div class="hero-carousel__viewport">
                    @foreach ($carouselSlides as $index => $slide)
                        <div
                            class="hero-carousel__slide{{ $index === 0 ? ' is-active' : '' }}"
                            data-hero-carousel-slide
                        >
                            <x-landing.responsive-image
                                :path="$slide['path']"
                                :alt="$slide['alt']"
                                width="640"
                                height="480"
                                :loading="$index === 0 ? 'eager' : 'lazy'"
                                :fetchpriority="$index === 0 ? 'high' : null"
                                sizes="(max-width: 760px) calc(100vw - 32px), 50vw"
                                picture-class="hero-carousel__picture"
                            />
                        </div>
                    @endforeach
                </div>
                @if (count($carouselSlides) > 1)
                    <div class="hero-carousel__dots" role="tablist" aria-label="Слайды баннера">
                        @foreach ($carouselSlides as $index => $slide)
                            <button
                                type="button"
                                class="hero-carousel__dot{{ $index === 0 ? ' is-active' : '' }}"
                                data-hero-carousel-dot
                                role="tab"
                                aria-label="Слайд {{ $index + 1 }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            ></button>
                        @endforeach
                    </div>
                @endif
            </figure>
        @endif
    </div>
</section>
@endif
