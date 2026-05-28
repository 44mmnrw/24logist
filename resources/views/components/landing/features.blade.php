@php
    $section = $landing->section('features');
@endphp

@if ($section)
<section class="features-section" id="features">
    @php
        $cards = $landing->blocks('features', 'card');
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

        <div class="features-grid">
            @foreach ($cards as $card)
                <article class="feature-card">
                    @if ($card->icon)
                        <div class="customer-card__icon"><x-landing.icon :name="$card->icon" /></div>
                    @endif
                    <h3>{{ $card->title }}</h3>
                    <p>{{ $card->description }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
