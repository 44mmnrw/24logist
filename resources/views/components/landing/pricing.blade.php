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
                    @if ($plan->tag)
                        <span class="pricing-hit">{{ $plan->tag }}</span>
                    @endif
                    <h3>{{ $plan->title }}</h3>
                    <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                    <div class="pricing-card__price">{{ $plan->price }}</div>
                    <p class="pricing-card__price-note">{{ $plan->description }}</p>
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

<section class="additional-options-section" aria-labelledby="additional-options-title">
    <div class="landing-shell additional-options-section__layout">
        <header class="additional-options-section__intro">
            <span class="additional-options-section__badge">Подключаются отдельно</span>
            <h2 id="additional-options-title">Дополнительные возможности</h2>
            <p>Расширяйте систему по мере роста — подключайте только то, что нужно именно вам, и платите только за это.</p>
        </header>

        <div class="additional-options-card">
            @foreach ([
                ['icon' => 'additional-seat.svg', 'title' => 'Дополнительное рабочее место', 'description' => 'Каждое место сверх тарифного лимита оплачивается отдельно.', 'price' => '1 200 ₽/мес'],
                ['icon' => 'additional-epd.svg', 'title' => 'Дополнительный пакет ЭПД', 'description' => 'Докупите пакет электронных перевозочных документов сверх включённого объёма.', 'price' => 'по пакетам'],
                ['icon' => 'additional-cloud.svg', 'title' => 'Дополнительное место в облаке', 'description' => 'Расширьте хранилище для документов и вложений на любой объём.', 'price' => 'по объёму'],
            ] as $option)
                <article class="additional-option">
                    <span class="additional-option__icon" aria-hidden="true">
                        <img src="{{ asset('images/landing/'.$option['icon']) }}" alt="">
                    </span>
                    <div class="additional-option__copy">
                        <h3>{{ $option['title'] }}</h3>
                        <p>{{ $option['description'] }}</p>
                    </div>
                    <strong>{{ $option['price'] }}</strong>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
