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
    @endphp

    <div class="landing-shell landing-hero__shell">
        <div class="landing-hero__content">
            @if ($section?->badge_text)
                <div class="landing-badge">
                    @if ($section->badge_icon)
                        <x-landing.icon :name="$section->badge_icon" />
                    @endif
                    <span>{{ $section->badge_text }}</span>
                </div>
            @endif

            @if ($section?->title)
                <h1>{{ $section->title }}</h1>
            @endif

            @if ($section?->subtitle)
                <h2 class="landing-hero__subtitle">{{ $section->subtitle }}</h2>
            @endif

            <ul class="hero-list">
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
                            <img
                                src="{{ $slide['url'] }}"
                                alt="{{ $slide['alt'] }}"
                                width="640"
                                height="480"
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                decoding="async"
                            >
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
