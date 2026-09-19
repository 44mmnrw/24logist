@php
    $section = $landing->section('pricing_wide');
    $plan = $landing->blocks('pricing_wide', 'plan')->first();
@endphp

@if ($section)
<section class="pricing-wide-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell">
        <header class="section-head section-head--wide pricing-wide-head">
            @if ($section->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section->subtitle)
                <p>{{ $section->subtitle }}</p>
            @endif
        </header>

        @if ($plan)
            <article @class(['pricing-card', 'pricing-card--wide', 'pricing-card--hit' => $plan->is_highlighted])>
                @if ($plan->tag || $plan->secondary_tag)
                    <div class="pricing-card__badges">
                        @if ($plan->tag)
                            <span class="pricing-hit">{{ $plan->tag }}</span>
                        @endif
                        @if ($plan->secondary_tag)
                            <span class="pricing-hit pricing-hit--secondary">{{ $plan->secondary_tag }}</span>
                        @endif
                    </div>
                @endif

                <div class="pricing-card--wide__heading">
                    <h3>{{ $plan->title }}</h3>
                    <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                </div>
                <div class="pricing-card--wide__details">
                    <div class="pricing-card__price">{{ $plan->price }}</div>
                    <p class="pricing-card__price-note">{{ $plan->description }}</p>
                </div>
                <div class="pricing-card--wide__features">
                    <ul>
                        @foreach ($plan->children->where('block_type', 'feature') as $feature)
                            <li>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="20" height="20" fill="none" aria-hidden="true" class="pricing-card__check">
                                    <use href="#icon-doc-check-circle" xlink:href="#icon-doc-check-circle" />
                                </svg>
                                {{ $feature->title }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if ($plan->button_text)
                    <div class="pricing-card--wide__action">
                        @if (filled($plan->link))
                            <a href="{{ \App\Support\LandingLinks::resolve($plan->link) }}" @class(['btn', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                                {{ $plan->button_text }}
                            </a>
                        @else
                            <button type="button" @class(['btn', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                                {{ $plan->button_text }}
                            </button>
                        @endif
                    </div>
                @endif
            </article>
        @endif
    </div>
</section>
@endif
