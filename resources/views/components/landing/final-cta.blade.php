@php $section = $landing->section('final_cta'); @endphp

@if ($section)
<section class="final-cta" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell">
        <div class="final-cta__box">
            <div class="final-cta__text">
                @if ($section?->title)
                    <h2>{{ $section->title }}</h2>
                @endif
                @if ($section?->description)
                    <p>{{ $section->description }}</p>
                @endif
            </div>
            <div class="final-cta__actions">
                @if ($section?->button_primary_text)
                    @if (filled($section->button_primary_url))
                        <a href="{{ \App\Support\LandingLinks::resolve($section->button_primary_url) }}" class="btn btn--primary">{{ $section->button_primary_text }}</a>
                    @else
                        <button type="button" class="btn btn--primary">{{ $section->button_primary_text }}</button>
                    @endif
                @endif
                @if ($section?->button_secondary_text)
                    @if (filled($section->button_secondary_url))
                        <a href="{{ \App\Support\LandingLinks::resolve($section->button_secondary_url) }}" class="btn btn--ghost-dark">{{ $section->button_secondary_text }}</a>
                    @else
                        <button type="button" class="btn btn--ghost-dark">{{ $section->button_secondary_text }}</button>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>
@endif
