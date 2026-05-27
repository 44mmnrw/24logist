<section class="landing-hero" id="hero">
    @php
        $section = $landing->section('hero');
        $extra = $section?->extra ?? [];
        $bullets = $landing->blocks('hero', 'bullet');
        $dashboardImage = \App\Support\LandingMedia::url($section?->dashboard_image ?? $extra['dashboard_image'] ?? null);
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

            <ul class="hero-list">
                @foreach ($bullets as $bullet)
                    <li>
                        @if ($bullet->icon)
                            <x-landing.icon :name="$bullet->icon" />
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

        @if ($dashboardImage)
            <figure class="dashboard-card">
                <img
                    src="{{ $dashboardImage }}"
                    alt="{{ $extra['dashboard_image_alt'] ?? 'Интерфейс ЛогистРу' }}"
                    width="640"
                    height="480"
                    loading="lazy"
                    decoding="async"
                >
            </figure>
        @endif
    </div>
</section>
