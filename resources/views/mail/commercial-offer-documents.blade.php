Здравствуйте, {{ $lead->name }}!

Во вложении два документа:
— коммерческое предложение для {{ $lead->offer_details['company'] }} с выбранным Вами составом тарифа и расчётом стоимости;
— подробное описание функциональных характеристик платформы логистРу.

Тариф: {{ $lead->offer_details['plan_title'] }}
Количество сотрудников: {{ $lead->offer_details['users'] }}
Период оплаты: {{ $lead->offer_details['billing_period'] === 'year' ? 'год' : 'месяц' }}
Расчётная стоимость: {{ number_format($lead->offer_details['total'], 0, ',', ' ') }} {{ $lead->offer_details['currency_suffix'] }}

Для подключения тестового доступа свяжитесь с нами:
info@24logist.ru
+7 (495) 109-25-44
https://24logist.ru
