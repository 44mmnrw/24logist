@php
    $section = $landing->section('pricing_wide');
    $plan = $landing->blocks('pricing_wide', 'plan')->first();
    $planExtra = is_array($plan?->extra) ? $plan->extra : [];
    $features = $plan?->children->where('block_type', 'feature')->values() ?? collect();
    $paidOptions = $plan?->children->where('block_type', 'paid_option')->values() ?? collect();
    $usersMin = max(1, (int) ($planExtra['users_min'] ?? 1));
    $usersMax = min(500, max($usersMin, (int) ($planExtra['users_max'] ?? 20)));
    $usersDefault = min($usersMax, max($usersMin, (int) ($planExtra['users_default'] ?? $usersMin)));
    $basePrice = max(0, (int) ($plan?->price ?? 0));
    $initialPrice = $basePrice * $usersDefault;
    $currencySuffix = trim((string) ($planExtra['currency_suffix'] ?? '₽/мес'));
    $userCountLabels = [
        1 => 'за одно рабочее место',
        2 => 'за два рабочих места',
        3 => 'за три рабочих места',
        4 => 'за четыре рабочих места',
        5 => 'за пять рабочих мест',
        6 => 'за шесть рабочих мест',
        7 => 'за семь рабочих мест',
        8 => 'за восемь рабочих мест',
        9 => 'за девять рабочих мест',
        10 => 'за десять рабочих мест',
        11 => 'за одиннадцать рабочих мест',
        12 => 'за двенадцать рабочих мест',
        13 => 'за тринадцать рабочих мест',
        14 => 'за четырнадцать рабочих мест',
        15 => 'за пятнадцать рабочих мест',
        16 => 'за шестнадцать рабочих мест',
        17 => 'за семнадцать рабочих мест',
        18 => 'за восемнадцать рабочих мест',
        19 => 'за девятнадцать рабочих мест',
        20 => 'за двадцать рабочих мест',
    ];
    $workplaceNoun = static function (int $count): string {
        $lastTwoDigits = $count % 100;
        $lastDigit = $count % 10;

        return $lastTwoDigits >= 11 && $lastTwoDigits <= 14
            ? 'рабочих мест'
            : match ($lastDigit) {
                1 => 'рабочее место',
                2, 3, 4 => 'рабочих места',
                default => 'рабочих мест',
            };
    };
    $initialUsersNote = $userCountLabels[$usersDefault]
        ?? 'за '.$usersDefault.' '.$workplaceNoun($usersDefault);
@endphp

@if ($section && $plan)
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

        <article
            @class(['pricing-card', 'pricing-card--wide', 'pricing-card--hit' => $plan->is_highlighted])
            data-wide-pricing
            data-base-price="{{ $basePrice }}"
            data-user-count-labels='@json($userCountLabels, JSON_UNESCAPED_UNICODE)'
        >
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

            <div class="pricing-card--wide__details">
                @if ($plan->title)
                    <h3>{{ $plan->title }}</h3>
                @endif
                @if ($plan->subtitle)
                    <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                @endif
                <div class="pricing-card__price" aria-live="polite">
                    <span data-wide-pricing-total>{{ number_format($initialPrice, 0, ',', ' ') }}</span>
                    @if ($currencySuffix !== '')
                        <small>{{ $currencySuffix }}</small>
                    @endif
                </div>
                <p
                    class="pricing-card__price-note"
                    data-wide-pricing-users-note
                >
                    {{ $initialUsersNote }}
                </p>
            </div>

            <div class="pricing-card--wide__features">
                <ul>
                    @foreach ($features as $feature)
                        <li>
                            <svg viewBox="0 0 16 16" width="20" height="20" fill="none" aria-hidden="true" class="pricing-card__check">
                                <use href="#icon-doc-check-circle" xlink:href="#icon-doc-check-circle" />
                            </svg>
                            {{ $feature->title }}
                        </li>
                    @endforeach
                </ul>

                <div class="pricing-card--wide__users">
                    <span>{{ $planExtra['users_label'] ?? 'Количество пользователей' }}</span>
                    <span class="pricing-card--wide__stepper">
                        <button type="button" data-wide-pricing-decrease aria-label="Уменьшить количество пользователей">−</button>
                        <input
                            type="number"
                            value="{{ $usersDefault }}"
                            min="{{ $usersMin }}"
                            max="{{ $usersMax }}"
                            readonly
                            aria-label="{{ $planExtra['users_label'] ?? 'Количество пользователей' }}"
                            data-wide-pricing-users
                        >
                        <button type="button" data-wide-pricing-increase aria-label="Увеличить количество пользователей">+</button>
                    </span>
                </div>
            </div>

            <div class="pricing-card--wide__additional-heading">
                <h4>{{ $planExtra['additional_title'] ?? 'Дополнительные возможности' }}</h4>
            </div>

            <div class="pricing-card--wide__configurator">
                @if ($paidOptions->isNotEmpty())
                    <div class="pricing-card--wide__options">
                        @foreach ($paidOptions as $option)
                            @php($optionPrice = max(0, (int) $option->price))
                            <label class="pricing-card--wide__option">
                                <input type="checkbox" value="{{ $optionPrice }}" data-option-id="{{ $option->id }}" data-wide-pricing-option>
                                <span class="pricing-card--wide__checkbox" aria-hidden="true"></span>
                                <span class="pricing-card--wide__option-title">{{ $option->title }}</span>
                                <strong>+{{ number_format($optionPrice, 0, ',', ' ') }} {{ $currencySuffix }}</strong>
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($plan->button_text)
                    <div class="pricing-card--wide__action">
                        <button type="button" data-commercial-offer-open @class(['btn', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                            {{ $plan->button_text }}
                        </button>
                    </div>
                @endif
            </div>
        </article>
    </div>
</section>
@endif
