# API реферальной программы

Реферальная программа и её финансовый журнал находятся в `24logistru`. Основная платформа позже передаёт сюда регистрации, оплаты и возвраты.

## Авторизация

Все запросы к `/api/v1/referrals/*` используют `Authorization: Bearer <REFERRAL_PLATFORM_API_SECRET>`. Секрет хранится только в переменных окружения двух приложений и не передаётся в браузер.

Деньги передаются целым количеством копеек, ставки возвращаются в basis points (`700` = `7%`). У каждого события должен быть стабильный уникальный `event_id`; повторная отправка безопасна.

## Регистрация

`POST /api/v1/referrals/registrations`

Обязательные поля: `external_registration_id`, `company_name`, `inn`. Поле `referral_code` необязательно. Можно сразу передать `external_account_id`. Для антифрода поддерживаются `existed_before`, `previously_paid`, `matching_bank_details`, `matching_owner`, `matching_phone`, `anomalous_activity`, `fraud_signals`.

Ответ содержит `attributed`, `status` и зафиксированный `discount_bps`. Решение о совместимости скидок принимает основная платформа: применяется максимум из реферальной и другой доступной скидки, а не сумма.

После создания аккаунта: `POST /api/v1/referrals/registrations/confirm` с `external_registration_id` и `external_account_id`.

Актуальную неиспользованную скидку можно получить через `GET /api/v1/referrals/discounts/{external_account_id}`.

## Оплата

`POST /api/v1/referrals/payments`

Поля: `event_id`, `external_payment_id`, `external_account_id`, `amount_minor`, необязательные `vat_minor`, `discount_minor`, `paid_at`, `is_test`, `currency`. База комиссии рассчитывается из фактически полученной суммы за вычетом переданного НДС. Тестовые события игнорируются.

## Возврат

`POST /api/v1/referrals/refunds`

Поля: `event_id`, `external_payment_id`, `refund_base_minor`, необязательное `refunded_at`. Возврат создаёт неизменяемую отрицательную корректировку; после уже выполненной выплаты она удерживается из будущего реестра.

## Регламент

Планировщик ежедневно переводит начисления после холда в `available`, а первого числа месяца создаёт черновые реестры при достижении индивидуального порога. Выплату подтверждает администратор с банковским идентификатором. CSV и XLSX доступны в разделе реестров.
