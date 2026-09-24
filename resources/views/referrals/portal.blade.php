<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Реферальная программа — ЛогистРу</title>
    @vite(['resources/css/app.css', 'resources/js/cookie-consent.js'])
</head>
<body>
<main class="referral-portal">
    <div class="referral-portal__shell">
        <header class="referral-portal__header">
            <div><p class="referral-portal__muted">ЛогистРу</p><h1>Реферальная программа</h1></div>
            <div><strong>{{ $participant->company_name }}</strong><br><span class="referral-portal__muted">ИНН {{ $participant->inn }}</span></div>
        </header>

        @if (session('status')) <div class="referral-portal__notice">{{ session('status') }}</div> @endif

        <section class="referral-portal__grid referral-portal__metrics">
            @foreach (['clicks' => 'Переходы', 'registrations' => 'Регистрации', 'paid_clients' => 'Оплатили', 'pending' => 'Ожидается', 'available' => 'Доступно', 'paid' => 'Выплачено'] as $key => $label)
                <article class="referral-portal__card referral-portal__metric"><span>{{ $label }}</span><strong>@if(in_array($key, ['pending','available','paid'])) {{ number_format($metrics[$key] / 100, 2, ',', ' ') }} ₽ @else {{ $metrics[$key] }} @endif</strong></article>
            @endforeach
        </section>

        <section class="referral-portal__card">
            <h2>Персональная ссылка</h2>
            <div class="referral-portal__link"><input value="{{ $personalLink }}" readonly><button type="button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Копировать</button></div>
            <p class="referral-portal__muted">Готовый текст: «Попробуйте ЛогистРу для управления перевозками: {{ $personalLink }}»</p>
        </section>

        <section class="referral-portal__grid">
            <article class="referral-portal__card">
                <h2>Оферта</h2>
                @if($participant->offer_accepted_at)
                    <p>Принята версия {{ $participant->offer_version }} — {{ $participant->offer_accepted_at->format('d.m.Y') }}.</p>
                @else
                    <form method="post" action="{{ URL::signedRoute('referrals.portal.offer', ['participant' => $participant->id]) }}">@csrf
                        <label><span><input type="checkbox" name="accepted" value="1" required style="width:auto"> Принимаю <a href="{{ $settings->offer_url }}" target="_blank" rel="noopener">актуальную оферту</a></span></label>
                        <button>Принять оферту</button>
                    </form>
                @endif
            </article>
            <article class="referral-portal__card referral-bank-card">
                <div class="referral-bank-card__header">
                    <h2>Основные реквизиты счёта</h2>
                    <span class="referral-bank-card__status {{ $participant->bank_details_verified_at ? 'is-verified' : '' }}">
                        {{ $participant->bank_details_verified_at ? 'Проверены' : 'Ожидают проверки' }}
                    </span>
                </div>
                <form class="referral-bank-form" method="post" action="{{ URL::signedRoute('referrals.portal.bank-details', ['participant' => $participant->id]) }}">@csrf
                    <label class="referral-bank-form__row"><span>Получатель</span><input name="recipient" required autocomplete="off"></label>
                    <label class="referral-bank-form__row"><span>Расчётный счёт</span><input name="account" required maxlength="20" inputmode="numeric" autocomplete="off"></label>
                    <label class="referral-bank-form__row"><span>Банк</span><input name="bank_name" required autocomplete="off"></label>
                    <label class="referral-bank-form__row"><span>БИК</span><input name="bik" required maxlength="9" inputmode="numeric" autocomplete="off"></label>
                    <label class="referral-bank-form__row"><span>Корр. счёт</span><input name="correspondent_account" required maxlength="20" inputmode="numeric" autocomplete="off"></label>
                    <div class="referral-bank-form__actions">
                        <span class="referral-portal__muted">После изменения реквизиты нужно проверить повторно.</span>
                        <button>Сохранить реквизиты</button>
                    </div>
                </form>
            </article>
            @if($settings->public_placements_enabled)
                <article class="referral-portal__card"><h2>Публичное размещение</h2><form method="post" action="{{ URL::signedRoute('referrals.portal.placements', ['participant' => $participant->id]) }}">@csrf
                    <label>Площадка<input name="platform" required></label><label>URL<input type="url" name="placement_url" required></label><label>Материал<textarea name="creative_text" rows="5" required></textarea></label><button>Отправить заявку</button>
                </form></article>
            @endif
        </section>

        @if($participant->placements->isNotEmpty())
            <section class="referral-portal__card"><h2>Публичные размещения</h2><div class="referral-portal__table"><table><thead><tr><th>Площадка</th><th>Статус</th><th>ERID</th><th>Ссылка после одобрения</th></tr></thead><tbody>
                @foreach($participant->placements as $placement)<tr><td>{{ $placement->platform }}</td><td>{{ $placement->status }}</td><td>{{ $placement->erid ?: 'Ожидает регистрации' }}</td><td>@if($placement->status === 'active' && $placement->erid)<a href="{{ url('/r/'.$placement->code) }}">{{ url('/r/'.$placement->code) }}</a><br><small>Реклама. ERID: {{ $placement->erid }}</small>@else — @endif</td></tr>@endforeach
            </tbody></table></div></section>
        @endif

        <section class="referral-portal__card"><h2>Приглашённые компании и зафиксированные условия</h2><div class="referral-portal__table"><table><thead><tr><th>Компания</th><th>ИНН</th><th>Статус</th><th>Комиссия</th><th>Период</th><th>Скидка</th><th>Холд</th></tr></thead><tbody>
            @forelse($participant->attributions as $item)<tr><td>{{ $item->referred_company_name ?: '—' }}</td><td>{{ $item->referred_inn }}</td><td>{{ $item->status }}</td><td>{{ number_format($item->commission_bps / 100, 2, ',', ' ') }} %</td><td>{{ $item->commission_months }} мес.</td><td>{{ number_format($item->invitee_discount_bps / 100, 2, ',', ' ') }} %</td><td>{{ $item->hold_days }} дн.</td></tr>@empty<tr><td colspan="7">Пока нет регистраций.</td></tr>@endforelse
        </tbody></table></div></section>

        <section class="referral-portal__card"><h2>Выплаты</h2><div class="referral-portal__table"><table><thead><tr><th>Период</th><th>Сумма</th><th>Статус</th><th>Банковский идентификатор</th></tr></thead><tbody>
            @forelse($participant->payouts as $payout)<tr><td>{{ $payout->period_start->format('d.m.Y') }}–{{ $payout->period_end->format('d.m.Y') }}</td><td>{{ number_format($payout->amount_minor / 100, 2, ',', ' ') }} ₽</td><td>{{ $payout->status }}</td><td>{{ $payout->payment_reference ?: '—' }}</td></tr>@empty<tr><td colspan="4">Выплат пока нет.</td></tr>@endforelse
        </tbody></table></div></section>
    </div>
</main>
<x-site.tracking />
</body>
</html>
