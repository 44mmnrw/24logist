@php
    $section = $landing->section('why');
@endphp

@if ($section)
<section class="why-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $cards = $landing->blocks('why', 'card');
        $stats = $landing->blocks('why', 'stat');
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
                    @if ($card->icon)
                        <div class="why-card__icon"><x-landing.icon :name="$card->icon" /></div>
                    @endif
                    <h3>{{ $card->title }}</h3>
                    <p>{{ $card->description }}</p>
                </article>
            @endforeach
        </div>

        <div class="why-stats">
            @foreach ($stats as $stat)
                <article>
                    <strong>{{ $stat->title }}</strong>
                    <span>{{ $stat->subtitle }}</span>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
