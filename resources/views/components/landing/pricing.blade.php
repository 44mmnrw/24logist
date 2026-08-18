@php
    $section = $landing->section('pricing');
@endphp

@if ($section)
<section class="pricing-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
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

        <div @class(['pricing-grid', 'pricing-grid--three' => $plans->count() === 3])>
            @foreach ($plans as $plan)
                <article @class(['pricing-card', 'pricing-card--hit' => $plan->is_highlighted])>
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
                    <h3>{{ $plan->title }}</h3>
                    <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                    <div class="pricing-card__price">{{ $plan->price }}</div>
                    <p class="pricing-card__price-note">{{ $plan->description }}</p>
                    <ul>
                        @foreach ($plan->children->where('block_type', 'feature') as $feature)
                            <li>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 16 16"
                                    width="20"
                                    height="20"
                                    fill="none"
                                    aria-hidden="true"
                                    class="pricing-card__check"
                                    style="display:block;width:20px;height:20px;flex:0 0 20px;color:#16a34a"
                                >
                                    <use href="#icon-doc-check-circle" xlink:href="#icon-doc-check-circle" />
                                </svg>
                                {{ $feature->title }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($plan->button_text)
                        @if (filled($plan->link))
                            <a href="{{ \App\Support\LandingLinks::resolve($plan->link) }}" @class(['btn', 'btn--full', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                                {{ $plan->button_text }}
                            </a>
                        @else
                            <button type="button" @class(['btn', 'btn--full', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                                {{ $plan->button_text }}
                            </button>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>

        @if (! empty($extra['footnote']) || ! empty($extra['footnote_link_text']))
            @php
                $quizLink = \App\Support\LandingLinks::resolve($landing->section('quiz')?->anchorLink() ?? '#quiz');
            @endphp
            <p class="pricing-foot">
                {{ $extra['footnote'] }}
                @if (! empty($extra['footnote_link_text']))
                    <a href="{{ \App\Support\LandingLinks::resolve($extra['footnote_link'] ?? $quizLink) }}">{{ $extra['footnote_link_text'] }}</a>
                @endif
            </p>
        @endif
    </div>
</section>
@endif
