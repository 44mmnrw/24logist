@php
    $section = $landing->section('pricing');
@endphp

@if ($section)
<section class="pricing-section" id="pricing">
    @php
        $extra = $section?->extra ?? [];
        $plans = $landing->blocks('pricing', 'plan');
    @endphp

    <div class="landing-shell">
        <header class="section-head section-head--wide pricing-head">
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section?->subtitle)
                <p>{{ $section->subtitle }}</p>
            @endif
        </header>

        <div class="pricing-grid">
            @foreach ($plans as $plan)
                <article @class(['pricing-card', 'pricing-card--hit' => $plan->is_highlighted])>
                    @if ($plan->tag)
                        <span class="pricing-hit">{{ $plan->tag }}</span>
                    @endif
                    <h3>{{ $plan->title }}</h3>
                    <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                    <div class="pricing-card__price">{{ $plan->price }}</div>
                    <ul>
                        @foreach ($plan->children->where('block_type', 'feature') as $feature)
                            <li>
                                @if ($feature->icon)
                                    <x-landing.icon :name="$feature->icon" />
                                @endif
                                {{ $feature->title }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($plan->button_text)
                        <button type="button" @class(['btn', 'btn--full', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                            {{ $plan->button_text }}
                        </button>
                    @endif
                </article>
            @endforeach
        </div>

        @if (! empty($extra['footnote']))
            <p class="pricing-foot">
                {{ $extra['footnote'] }}
                @if (! empty($extra['footnote_link_text']))
                    <a href="{{ $extra['footnote_link'] ?? '#quiz' }}">{{ $extra['footnote_link_text'] }}</a>
                @endif
            </p>
        @endif
    </div>
</section>
@endif
