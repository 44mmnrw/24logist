@php
    $section = $landing->section('footer');
@endphp

@if ($section)
<footer class="landing-footer">
    @php
        $extra = $section?->extra ?? [];
        $columns = $landing->blocks('footer', 'footer_column');
    @endphp

    <div class="landing-shell landing-footer__top">
        <div class="landing-footer__brand">
            <a class="brand brand--footer" href="{{ \App\Support\LandingLinks::resolve('#hero') }}">
                <x-landing.logo variant="footer" />
            </a>
            @if ($section?->description)
                <p>{{ $section->description }}</p>
            @endif
        </div>

        @foreach ($columns as $column)
            <div class="landing-footer__col">
                <h3>{{ $column->title }}</h3>
                @foreach ($column->children->where('block_type', 'footer_link') as $link)
                    <a href="{{ \App\Support\LandingLinks::resolve($link->link) }}">
                        @if ($link->icon)
                            <x-landing.icon :name="$link->icon" />
                        @endif
                        {{ $link->title }}
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="landing-footer__bottom">
        <div class="landing-shell landing-footer__bottom-shell">
            <span>{{ $extra['copyright'] ?? '' }}</span>
            <span>
                <a href="{{ route('blog.index') }}">Блог</a>
                @if (app(\App\Services\SiteSettingsService::class)->communityEnabled())
                    <span aria-hidden="true"> · </span><a href="{{ route('community.index') }}">Сообщество</a>
                @endif
                @if (! empty($extra['tagline']))
                    <span aria-hidden="true"> · </span>{{ $extra['tagline'] }}
                @endif
            </span>
        </div>
    </div>
</footer>
@endif
