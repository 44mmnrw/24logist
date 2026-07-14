@php
    $section = $landing->section('additional_options');
@endphp

@if ($section)
<section class="additional-options-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif aria-labelledby="additional-options-title">
    <div class="landing-shell additional-options-section__layout">
        <header class="additional-options-section__intro">
            @if ($section->kicker)
                <span class="additional-options-section__badge">{{ $section->kicker }}</span>
            @endif
            @if ($section->title)
                <h2 id="additional-options-title">{{ $section->title }}</h2>
            @endif
            @if ($section->subtitle)
                <p>{{ $section->subtitle }}</p>
            @endif
        </header>
        <div class="additional-options-card">
            @foreach ($landing->blocks('additional_options', 'option') as $option)
                <article class="additional-option">
                    @if ($option->icon)
                        <span class="additional-option__icon" aria-hidden="true"><x-landing.icon :name="$option->icon" /></span>
                    @endif
                    <div class="additional-option__copy">
                        <h3>{{ $option->title }}</h3>
                        @if ($option->description)<p>{{ $option->description }}</p>@endif
                    </div>
                    @if ($option->price)<strong>{{ $option->price }}</strong>@endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
