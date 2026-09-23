<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Коммерческое предложение логистРу</title>
    <style>
        @page { margin: 78pt 48pt 64pt; }
        body { margin: 0; font: 10.2pt/1.3 "DejaVu Sans", sans-serif; color: #202a38; }
        .stripe { position: fixed; top: -78pt; left: -48pt; right: -48pt; height: 5pt; background: #1264ef; }
        .stripe span { display: block; float: right; width: 16%; height: 5pt; background: #14ba48; }
        header { position: fixed; top: -58pt; left: 0; right: 0; height: 50pt; }
        header img { width: 180pt; height: auto; }
        .brand-caption { position: absolute; top: 3pt; right: 0; text-align: right; font-size: 7.5pt; line-height: 1.6; color: #68768a; }
        footer { position: fixed; bottom: -45pt; left: 0; right: 0; border-top: .5pt solid #d9d9d9; padding-top: 8pt; color: #68768a; font-size: 7pt; line-height: 1.6; }
        .eyebrow { color: #1264ef; font-weight: bold; font-size: 8pt; margin: 0 0 10pt; }
        h1 { font-size: 24pt; line-height: 1.15; color: #000; margin: 0 0 13pt; page-break-after: avoid; }
        h2 { font-size: 13pt; line-height: 1.35; color: #000; margin: 14pt 0 8pt; page-break-after: avoid; }
        h2 span { color: #1264ef; }
        p { margin: 0 0 8pt; }
        .small { font-size: 8.7pt; color: #68768a; line-height: 1.45; }
        .recipient { margin: 10pt 0; word-wrap: break-word; }
        .recipient p { margin: 0 0 3pt; }
        .lead { font-size: 10.7pt; }
        ul { margin: 0 0 7pt; padding-left: 14pt; }
        li { margin: 0 0 4pt; padding-left: 1pt; }
        li::marker { color: #1264ef; }
        .terms { page-break-before: always; }
        table { width: 100%; border-collapse: collapse; margin: 10pt 0 11pt; table-layout: fixed; font-size: 9.6pt; line-height: 1.25; }
        th, td { border: .5pt solid #d9d9d9; text-align: left; padding: 4pt 10pt; vertical-align: middle; word-wrap: break-word; }
        th { background: #10244b; color: #fff; font-size: 9pt; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        tr:nth-child(even) td { background: #f3f6fa; }
        .total td { color: #10244b; font-weight: bold; }
        .legal { font-size: 9.3pt; line-height: 1.3; page-break-inside: avoid; }
        .contact { margin-top: 8pt; page-break-inside: avoid; position: relative; min-height: 38pt; }
        .contact p { margin-bottom: 0; }
        .signature { position: absolute; left: 125pt; top: 10pt; width: 96pt; height: 32pt; }
    </style>
</head>
<body>
@php
    $money = static fn (int $value): string => number_format($value, 0, ',', ' ');
    $yearly = $offer['billing_period'] === 'year';
@endphp
<div class="stripe"><span></span></div>
<header>
    <img src="{{ $logo }}" alt="логистРу">
    <div class="brand-caption">ОБЛАЧНАЯ TMS-ПЛАТФОРМА<br>24logist.ru</div>
</header>
<footer>
    24logist.ru &nbsp; | &nbsp; info@24logist.ru &nbsp; | &nbsp; +7 (495) 109-25-44<br>
    логистРу · Коммерческое предложение № КП-{{ $lead->id }}
</footer>

<div class="eyebrow">ДЛЯ ЭКСПЕДИТОРСКИХ КОМПАНИЙ</div>
<h1>Коммерческое предложение</h1>
<p class="small"><b>От</b> ООО «Энерви Групп»<br>ИНН 5074081476 · КПП 507401001</p>
<div class="recipient">
    <p><b>Кому:</b> {{ $offer['company'] }}</p>
    <p><b>ИНН:</b> {{ $offer['inn'] }}</p>
    <p><b>Контактное лицо:</b> {{ $lead->name }}</p>
    <p><b>Email:</b> {{ $lead->email }}<br><b>Телефон:</b> {{ $lead->phone }}</p>
    <p class="small">№ КП-{{ $lead->id }} от {{ $lead->created_at->format('d.m.Y') }}</p>
</div>
<p class="lead">Предлагаем Вашей компании доступ к облачной TMS-платформе <b>логистРу</b> для учёта перевозок, работы с перевозочными документами и электронного взаимодействия с участниками перевозки.</p>
<p>Система разработана для экспедиторских компаний. Единый интерфейс помогает организовать работу менеджеров, контролировать оплаты и сократить повторный ввод данных.</p>
<h2><span>01</span> Тестовый период 14 дней</h2>
<p>Оцените возможности платформы на рабочих задачах Вашей компании. В тестовый период доступны базовые разделы и инструменты:</p>
<ul>
    <li>Калькулятор маршрута.</li>
    <li>Банковские выписки и финансовые отчёты.</li>
    <li>Модули для работы с ЭПД и бумажными документами.</li>
    <li>Интеграция с оператором ЭДО / ИС ЭПД ООО «Эвотор ОФД».</li>
    <li>Справочники государственных реестров и проверка контрагентов.</li>
    <li>Изолированный настраиваемый доступ для каждого менеджера.</li>
    <li>Хранение резервных копий ЭПД.</li>
    <li>300 МБ на аккаунт для хранения скан-копий документов.</li>
</ul>
<h2><span>02</span> Дополнительные разделы</h2>
<ul>
    <li><b>Счета на оплату</b> и предусмотренные сервисом формы бухгалтерских документов.</li>
    <li><b>Нормализация адресов по ФИАС.</b></li>
</ul>

<div class="terms">
    <div class="eyebrow">КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ</div>
    <h1>Стоимость и подключение</h1>
    <p>Расчёт по выбранному Вами составу тарифа на {{ $lead->created_at->format('d.m.Y') }}.</p>
    <table>
        <thead><tr><th style="width: 39%">Параметр</th><th>Индивидуальные условия</th></tr></thead>
        <tbody>
            <tr><td>Тарифный план</td><td>{{ $offer['plan_title'] }}</td></tr>
            <tr><td>Количество сотрудников</td><td>{{ $offer['users'] }}</td></tr>
            <tr><td>Период оплаты</td><td>{{ $yearly ? 'Год — 12 месяцев' : 'Месяц' }}</td></tr>
            <tr><td>Базовый доступ</td><td>{{ $offer['users'] }} × {{ $money($offer['unit_price']) }} = {{ $money($offer['unit_price'] * $offer['users']) }} ₽/мес</td></tr>
            @forelse ($offer['options'] as $option)
                <tr><td>{{ $option['title'] }}</td><td>{{ $money($option['price']) }} ₽/мес за аккаунт</td></tr>
            @empty
                <tr><td>Дополнительные функции</td><td>Не выбраны</td></tr>
            @endforelse
            <tr class="total"><td>Итого за {{ $yearly ? 'год' : 'месяц' }}</td><td>{{ $money($offer['total']) }} {{ $offer['currency_suffix'] }}</td></tr>
        </tbody>
    </table>
    <p class="small">Налоговые условия и параметры обмена ЭДО / ЭПД согласовываются в договоре.</p>
    <h2><span>03</span> Правовые условия</h2>
    <p class="legal"><b>Доступ к программе.</b> Право использования системы «логистРу» предоставляется на условиях простой (неисключительной) лицензии. Условия приобретения лицензии и использования системы изложены в публичной оферте.</p>
    <p class="legal"><b>Электронный документооборот.</b> Обмен ЭДО / ЭПД, передача данных в ГИС ЭПД и роуминг доступны в рамках подключённых услуг ООО «Эвотор ОФД». Их состав и стоимость согласовываются при подключении.</p>
    <p class="legal"><b>Персональные данные.</b> Обработка данных регулируется Федеральным законом от 27.07.2006 № 152-ФЗ и опубликованной политикой обработки персональных данных. Обязанности сторон и меры защиты устанавливаются законодательством и договором.</p>
    <p class="legal"><b>Российское программное обеспечение.</b> Реестровая запись № 34983 от 31.08.2026 в Едином реестре российских программ для ЭВМ и баз данных.</p>
    <p class="small">Настоящий документ предназначен для обсуждения условий сотрудничества и не является офертой, в том числе публичной, в смысле статей 435 и 437 ГК РФ. Обязательства сторон возникают после заключения договора в согласованной форме.</p>
    <div class="contact">
        <p class="small">Директор по развитию сервиса<br>Станислав Аристов /</p>
        <img class="signature" src="{{ $signature }}" alt="Подпись Станислава Аристова">
    </div>
</div>
</body>
</html>
