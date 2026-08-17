@php
    $section = $landing->section('why');
@endphp

@if ($section)
<section class="why-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $cards = $landing->blocks('why', 'card');
        $extra = $section->extra ?? [];
    @endphp

    <div class="landing-shell">
        <header class="section-head section-head--wide">
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section?->subtitle)
                <p>{{ $section->subtitle }}</p>
            @endif
        </header>

        <div class="why-grid">
            @foreach ($cards as $card)
                <article class="why-card">
                    <span class="why-card__number" aria-hidden="true">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    @if ($iconAsset = data_get($card->extra, 'icon_asset'))
                        <div class="why-card__icon">
                            <img src="{{ asset($iconAsset) }}" alt="" width="24" height="24">
                        </div>
                    @endif
                    <h3>{{ $card->title }}</h3>
                    <p>{{ $card->description }}</p>
                    @if ($card->tag)
                        <span class="why-card__tag"><span aria-hidden="true"></span>{{ $card->tag }}</span>
                    @endif
                </article>
            @endforeach
        </div>

        @if (! empty($extra['quote']))
            <figure class="why-quote">
                <blockquote>«{{ $extra['quote'] }}»</blockquote>
                <figcaption>
                    @if (! empty($extra['quote_initials']))
                        <span class="why-quote__avatar" aria-hidden="true">{{ $extra['quote_initials'] }}</span>
                    @endif
                    <span class="why-quote__author">
                        @if (! empty($extra['quote_author']))
                            <strong>{{ $extra['quote_author'] }}</strong>
                        @endif
                        @if (! empty($extra['quote_handle']))
                            <span>{{ $extra['quote_handle'] }}</span>
                        @endif
                    </span>
                </figcaption>
            </figure>
        @endif
    </div>
</section>
@endif
