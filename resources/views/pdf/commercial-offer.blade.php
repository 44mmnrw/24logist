<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Коммерческое предложение логистРу</title>
    <style>
        @page { margin: 28pt 48pt 34pt; }
        @page :first { margin: 78pt 48pt 50pt; }
        body { margin: 0; font: 10.2pt/1.3 "DejaVu Sans", sans-serif; color: #202a38; }
        .stripe { position: absolute; top: -78pt; left: -48pt; right: -48pt; height: 5pt; background: #1264ef; }
        .stripe span { display: block; float: right; width: 16%; height: 5pt; background: #14ba48; }
        header { position: absolute; top: -58pt; left: 0; right: 0; height: 50pt; }
        header img { width: 180pt; height: auto; }
        .brand-caption { position: absolute; top: 3pt; right: 0; text-align: right; font-size: 7.5pt; line-height: 1.6; color: #68768a; }
        footer { position: absolute; bottom: -45pt; left: 0; right: 0; border-top: .5pt solid #d9d9d9; padding-top: 8pt; color: #68768a; font-size: 7pt; line-height: 1.6; }
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
        .overview { font-size: 9.2pt; line-height: 1.2; }
        .overview h1 { margin-bottom: 10pt; }
        .overview h2 { margin: 8pt 0 5pt; font-size: 12.5pt; line-height: 1.25; }
        .overview h2 .detail { display: block; margin-top: 1pt; color: #000; }
        .overview p { margin-bottom: 4pt; }
        .overview .lead { font-size: 9.6pt; }
        .overview .recipient { margin: 6pt 0; font-size: 8.8pt; line-height: 1.2; }
        .overview .recipient p { margin-bottom: 1pt; }
        .overview ul { margin-bottom: 4pt; }
        .overview li { margin-bottom: 1pt; page-break-inside: avoid; }
        .feature-group { page-break-inside: avoid; }
        .overview--long { font-size: 8.7pt; line-height: 1.15; }
        .overview--long h1 { font-size: 22pt; margin-bottom: 8pt; }
        .overview--long h2 { margin: 8pt 0 5pt; }
        .overview--long p { margin-bottom: 4pt; }
        .overview--long .lead { font-size: 9pt; }
        .overview--long .recipient { font-size: 8.2pt; line-height: 1.15; }
        .overview--long li { margin-bottom: 0; }
        .terms { page-break-before: always; }
        table { width: 100%; border-collapse: collapse; margin: 8pt 0 10pt; table-layout: fixed; font-size: 9.6pt; line-height: 1.25; }
        th, td { border: .5pt solid #d9d9d9; text-align: left; padding: 7pt 10pt; vertical-align: middle; word-wrap: break-word; }
        th { background: #10244b; color: #fff; font-size: 9pt; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        tr:nth-child(even) td { background: #f3f6fa; }
        .legal { font-size: 9.3pt; line-height: 1.3; }
        .contact { margin-top: 8pt; page-break-inside: avoid; }
        .contact p { margin-bottom: 5pt; }
        .signature-line { color: #68768a; letter-spacing: .3pt; }
    </style>
</head>
<body>
@php
    $money = static fn (int $value): string => number_format($value, 0, ',', ' ');
    $longRecipient = mb_strlen($offer['company'].$lead->name.$lead->email) > 255;
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

<div class="overview{{ $longRecipient ? ' overview--long' : '' }}">
<div class="eyebrow">ДЛЯ ЭКСПЕДИТОРСКИХ КОМПАНИЙ</div>
<h1>Коммерческое предложение</h1>
<p class="small"><b>От</b> ООО «Энерви Групп»<br>ИНН 5074081476 · КПП 507401001</p>
<div class="recipient">
    <p><b>Кому:</b> {{ $offer['company'] }}</p>
    <p><b>ИНН:</b> {{ $offer['inn'] }}</p>
    <p><b>Контактное лицо:</b> {{ $lead->name }} · {{ $lead->email }} · {{ $lead->phone }}</p>
    <p class="small">№ КП-{{ $lead->id }} от {{ $lead->created_at->format('d.m.Y') }}</p>
</div>
<p class="lead">Предлагаем вашей компании доступ к облачной TMS-системе учёта, включающей функционал по формированию, учёту и обмену электронными перевозочными документами (ЭПД).</p>
<p>Система разработана для экспедиторских компаний. Единый интерфейс помогает организовать работу менеджеров, контролировать оплаты, сократить повторный ввод и избавить от ошибок в документообороте.</p>
<h2><span>01</span> Тестовый период 14 дней <span class="detail">Включает в себя подключение к ЭДО, добавление сотрудников и настройку доступа, отработку сценариев экспедирования и завершения документооборота, плавный переход от бумаги на ЭПД.</span></h2>
<p>Оцените возможности платформы на рабочих процессах в вашей компании. В тестовый период доступны базовые разделы и инструменты:</p>
<ul>
    <li>Калькулятор маршрута</li>
    <li>Банковские выписки и отчёты</li>
    <li>Модуль для работы с ЭПД</li>
    <li>Шаблоны XML-файлов</li>
    <li>Встроенный оператор ЭДО / ЭПД ООО «Эвотор ОФД»</li>
    <li>Модуль для работы с бумажными документами</li>
    <li>Справочники государственных реестров</li>
    <li>Сервисы проверки контрагентов</li>
    <li>Изолированный доступ для каждого менеджера</li>
    <li>Хранение резервных копий ЭПД</li>
    <li>300 МБ для хранения скан-копий документов</li>
</ul>
<div class="feature-group">
<h2><span>02</span> Дополнительные разделы</h2>
<ul>
    <li><b>Счета на оплату</b> и предусмотренные сервисом формы бухгалтерских документов, а также разные виды обмена и экспорта.</li>
    <li><b>Нормализация адресов по коду ФИАС:</b> сервис помогает при вводе и копировании адреса.</li>
</ul>
<p class="small">Состав подключаемых дополнительных функций и условия их использования согласовываются отдельно.</p>
</div>
</div>

<div class="terms">
    <div class="eyebrow">КОММЕРЧЕСКОЕ ПРЕДЛОЖЕНИЕ</div>
    <h1>Стоимость и подключение</h1>
    <p>После тестового периода стоимость доступа определяется выбранным тарифом и количеством сотрудников. Индивидуальные условия фиксируются ниже и в договоре.</p>
    <table>
        <thead><tr><th style="width: 39%">Параметр</th><th>Индивидуальные условия</th></tr></thead>
        <tbody>
            <tr><td>Тарифный план</td><td>{{ $offer['plan_title'] }}</td></tr>
            <tr><td>Количество сотрудников</td><td>{{ $offer['users'] }}</td></tr>
            <tr><td>Стоимость доступа</td><td>{{ $money($offer['total']) }} {{ $offer['currency_suffix'] }}</td></tr>
            <tr><td>НДС</td><td>Условия определяются договором</td></tr>
            <tr><td>Дополнительные функции</td><td>{{ $offer['options'] === [] ? 'Не выбраны' : implode(', ', array_column($offer['options'], 'title')) }}</td></tr>
            <tr><td>Обмен ЭДО / ЭПД</td><td>Объём и стоимость согласовываются при подключении</td></tr>
            <tr><td>Срок действия предложения</td><td>До {{ $lead->created_at->copy()->addDays(30)->format('d.m.Y') }}</td></tr>
        </tbody>
    </table>
    <h2><span>03</span> Правовые условия</h2>
    <p class="legal"><b>Доступ к программе.</b> Право использования платформы, состав функций, порядок оплаты, условия поддержки и хранения данных определяются договором и применимыми условиями использования. Исключительное право на программу пользователю не передаётся.</p>
    <p class="legal"><b>Электронный документооборот.</b> Обмен через ООО «Эвотор ОФД», передача данных в ГИС ЭПД и роуминг осуществляются в рамках подключённых услуг оператора. Состав и стоимость этих услуг согласовываются при подключении.</p>
    <p class="legal"><b>Персональные данные.</b> Обработка персональных данных осуществляется в соответствии с Федеральным законом от 27.07.2006 № 152-ФЗ и опубликованной политикой обработки персональных данных. Порядок обработки данных, распределение обязанностей сторон и меры защиты определяются законодательством и договором.</p>
    <p class="legal"><b>Российское программное обеспечение.</b> Реестровая запись № 34983 от 31.08.2026 в Едином реестре российских программ для ЭВМ и баз данных.</p>
    <p class="small">Настоящий документ предназначен для обсуждения условий сотрудничества и не является офертой, в том числе публичной, в смысле статей 435 и 437 ГК РФ. Обязательства сторон возникают после заключения договора в согласованной форме.</p>
    <div class="contact">
        <p><b>Для подключения тестового доступа</b> &nbsp;&nbsp;&nbsp; info@24logist.ru · +7 (495) 109-25-44</p>
        <p class="small">Директор по развитию сервиса</p>
        <p class="signature-line">________________________________ / ____________________</p>
    </div>
</div>
</body>
</html>
