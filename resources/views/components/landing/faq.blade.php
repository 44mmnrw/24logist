<section class="faq-section" id="faq">
    @php
        $section = $landing->section('faq');
        $extra = $section?->extra ?? [];
        $items = $landing->blocks('faq', 'faq');
    @endphp

    <div class="faq-box">
        <header class="section-head section-head--center">
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
        </header>
        <div class="faq-list">
            @foreach ($items as $item)
                <details class="faq-item">
                    <summary class="faq-item__question">
                        <span>{{ $item->title }}</span>
                        @if (! empty($extra['toggle_icon']))
                            <x-landing.icon :name="$extra['toggle_icon']" class="faq-item__icon" />
                        @endif
                    </summary>
                    @if ($item->description)
                        <div class="faq-item__answer">
                            <p>{{ $item->description }}</p>
                        </div>
                    @endif
                </details>
            @endforeach
        </div>
    </div>
</section>
