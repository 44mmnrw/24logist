@php
    $section = $landing->section('mobile');
@endphp

@if ($section)
<section class="mobile-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $extra = $section?->extra ?? [];
        $bullets = $landing->blocks('mobile', 'bullet');
        $mobileImage = $section?->mobile_image ?? $extra['mobile_image'] ?? null;
    @endphp

    <div class="landing-shell mobile-section__shell">
        <div class="mobile-copy">
            @if ($section?->badge_text)
                <div class="landing-badge">
                    @if ($section->badge_icon)
                        <x-landing.icon :name="$section->badge_icon" />
                    @endif
                    <span>{{ $section->badge_text }}</span>
                </div>
            @endif
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section?->description)
                <p>{{ $section->description }}</p>
            @endif
            <ul class="hero-list mobile-copy__list">
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
        </div>

        <div class="mobile-showcase">
            @if (! empty($extra['pill_left_text']))
                <span class="mobile-pill mobile-pill--left">
                    @if (! empty($extra['pill_left_icon']))
                        <x-landing.icon :name="$extra['pill_left_icon']" />
                    @endif
                    {{ $extra['pill_left_text'] }}
                </span>
            @endif
            @if (! empty($extra['pill_right_text']))
                <span class="mobile-pill mobile-pill--right">
                    @if (! empty($extra['pill_right_icon']))
                        <x-landing.icon :name="$extra['pill_right_icon']" />
                    @endif
                    {{ $extra['pill_right_text'] }}
                </span>
            @endif
            <div class="mobile-phone">
                <div class="mobile-phone__screen mobile-phone__screen--image">
                    @if ($mobileImage)
                        <x-landing.responsive-image
                            :path="$mobileImage"
                            :alt="$extra['mobile_image_alt'] ?? 'Мобильный интерфейс ЛогистРу'"
                            width="276"
                            height="576"
                            loading="lazy"
                            sizes="276px"
                        />
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endif
