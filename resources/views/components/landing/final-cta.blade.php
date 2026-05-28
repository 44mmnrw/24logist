@php $section = $landing->section('final_cta'); @endphp

@if ($section)
<section class="final-cta" id="final-cta">
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
                    <button type="button" class="btn btn--primary">{{ $section->button_primary_text }}</button>
                @endif
                @if ($section?->button_secondary_text)
                    <button type="button" class="btn btn--ghost-dark">{{ $section->button_secondary_text }}</button>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
