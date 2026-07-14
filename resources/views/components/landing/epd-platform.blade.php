@php
    $section = $landing->section('epd_platform');
@endphp

@if ($section)
<section class="epd-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell">
        <header class="epd-section__head">
            <div class="epd-section__intro">
                <span class="epd-section__icon">
                    <x-landing.icon :name="$section->badge_icon" />
                </span>
                <div>
                    @if ($section->title)
                        <h2>{{ $section->title }}</h2>
                    @endif
                    @if ($section->subtitle)
                        <p>{{ $section->subtitle }}</p>
                    @endif
                </div>
            </div>

            @if ($section->button_primary_text)
                @if (filled($section->button_primary_url))
                    <a class="btn btn--ghost epd-section__details" href="{{ \App\Support\LandingLinks::resolve($section->button_primary_url) }}">
                        {{ $section->button_primary_text }}
                    </a>
                @else
                    <button class="btn btn--ghost epd-section__details" type="button">
                        {{ $section->button_primary_text }}
                    </button>
                @endif
            @endif
        </header>

        <div class="epd-packages" aria-label="Пакеты документов ЭПД">
            @foreach ($landing->blocks('epd_platform', 'package') as $package)
                <article class="epd-package">
                    <div>
                        <h3>{{ $package->title }}</h3>
                        <p class="epd-package__unit">{{ $package->subtitle }}</p>
                    </div>
                    <div>
                        <p class="epd-package__price">{{ $package->price }}</p>
                        <p class="epd-package__rate">{{ $package->description }}</p>
                    </div>
                    @if ($package->button_text)
                        @if (filled($package->link))
                            <a class="btn btn--ghost btn--full" href="{{ \App\Support\LandingLinks::resolve($package->link) }}">{{ $package->button_text }}</a>
                        @else
                            <button class="btn btn--ghost btn--full" type="button">{{ $package->button_text }}</button>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
