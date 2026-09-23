<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommercialOfferLeadRequest;
use App\Http\Requests\StoreContactLeadRequest;
use App\Http\Requests\StoreEpdPresentationLeadRequest;
use App\Http\Requests\StoreQuizLeadRequest;
use App\Models\LandingBlock;
use App\Models\LandingLead;
use App\Services\CommercialOfferDeliveryService;
use App\Services\SmartCaptchaService;
use App\Support\LandingLeadQuizAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LandingLeadController extends Controller
{
    public function storeCommercialOffer(StoreCommercialOfferLeadRequest $request, SmartCaptchaService $captcha, CommercialOfferDeliveryService $offers): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Заявка принята.'], 201);
        }

        $captcha->validate('commercial_offer', (string) $request->validated('smart_token', ''), $request->ip(), $request->getHost());

        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->where('is_active', true)
            ->first();

        if (! $plan || ! is_numeric($plan->price)) {
            throw ValidationException::withMessages([
                'users' => 'Тариф временно недоступен для расчёта. Обновите страницу и попробуйте ещё раз.',
            ]);
        }

        $extra = is_array($plan?->extra) ? $plan->extra : [];
        $minimumUsers = max(1, (int) ($extra['users_min'] ?? 1));
        $maximumUsers = min(500, max($minimumUsers, (int) ($extra['users_max'] ?? 500)));
        $users = min($maximumUsers, max($minimumUsers, $request->integer('users')));
        $optionIds = collect($request->validated('option_ids', []))->map(fn ($id): int => (int) $id)->all();
        $billingPeriod = $request->validated('billing_period', 'month') === 'year' ? 'year' : 'month';
        $periodMonths = $billingPeriod === 'year' ? 12 : 1;
        $options = $plan->children()->where('block_type', 'paid_option')->where('is_active', true)
            ->whereIn('id', $optionIds)->orderBy('sort_order')->get();

        if ($options->count() !== count($optionIds)) {
            throw ValidationException::withMessages([
                'option_ids' => 'Состав дополнительных функций изменился. Обновите страницу и выберите их заново.',
            ]);
        }
        $total = ((max(0, (int) ($plan?->price ?? 0)) * $users) + $options->sum(fn (LandingBlock $option): int => max(0, (int) $option->price))) * $periodMonths;
        $currencySuffix = trim((string) ($billingPeriod === 'year'
            ? ($extra['year_currency_suffix'] ?? '')
            : ($extra['currency_suffix'] ?? '')));

        $lead = LandingLead::query()->create([
            'type' => LandingLead::TYPE_COMMERCIAL_OFFER,
            'status' => LandingLead::STATUS_NEW,
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->string('email')->toString(),
            'quiz_answers' => [
                ['question' => 'Название компании', 'answer' => $request->string('company')->toString()],
                ['question' => 'ИНН', 'answer' => $request->string('inn')->toString()],
                ['question' => (string) ($extra['users_label'] ?? ''), 'answer' => (string) $users],
                ['question' => (string) ($extra['period_label'] ?? ''), 'answer' => $billingPeriod === 'year'
                    ? (string) ($extra['year_label'] ?? '')
                    : (string) ($extra['month_label'] ?? '')],
                ['question' => (string) ($extra['additional_title'] ?? ''), 'answer' => $options->pluck('title')->implode(', ') ?: 'Не выбраны'],
                ['question' => 'Расчётная стоимость', 'answer' => number_format($total, 0, ',', ' ').($currencySuffix !== '' ? ' '.$currencySuffix : '')],
            ],
            'recommended_plan_id' => $plan?->id,
            'recommended_plan_title' => $plan?->title,
            'offer_details' => [
                'company' => $request->string('company')->toString(),
                'inn' => $request->string('inn')->toString(),
                'plan_title' => $plan->title,
                'users' => $users,
                'billing_period' => $billingPeriod,
                'period_months' => $periodMonths,
                'unit_price' => max(0, (int) $plan->price),
                'options' => $options->map(fn (LandingBlock $option): array => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'price' => max(0, (int) $option->price),
                ])->all(),
                'total' => $total,
                'currency_suffix' => $currencySuffix,
            ],
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $sent = $offers->send($lead);

        return response()->json([
            'message' => $sent
                ? 'Коммерческое предложение и описание функциональных характеристик отправлены на указанный email.'
                : 'Заявка сохранена, но отправить документы автоматически не удалось. Мы свяжемся с вами.',
            'documents_sent' => $sent,
            'id' => $lead->id,
        ], 201);
    }

    public function storeQuiz(StoreQuizLeadRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Заявка принята.'], 201);
        }

        $answers = LandingLeadQuizAnswers::normalize($request->validated('answers'));

        if ($answers === []) {
            return response()->json([
                'message' => 'Не удалось сохранить ответы квиза. Попробуйте ещё раз.',
            ], 422);
        }

        $lead = LandingLead::query()->create([
            'type' => LandingLead::TYPE_QUIZ,
            'status' => LandingLead::STATUS_NEW,
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->string('email')->toString() ?: null,
            'quiz_answers' => $answers,
            'recommended_plan_id' => $request->integer('recommended_plan_id') ?: null,
            'recommended_plan_title' => $request->string('recommended_plan_title')->toString() ?: null,
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Заявка принята.',
            'id' => $lead->id,
        ], 201);
    }

    public function storeContact(StoreContactLeadRequest $request, SmartCaptchaService $captcha): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Сообщение отправлено.'], 201);
        }

        $captcha->validate('contact', (string) $request->validated('smart_token', ''), $request->ip(), $request->getHost());

        $lead = LandingLead::query()->create([
            'type' => LandingLead::TYPE_CONTACT,
            'status' => LandingLead::STATUS_NEW,
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->string('email')->toString() ?: null,
            'message' => $request->string('message')->toString() ?: null,
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Сообщение отправлено.',
            'id' => $lead->id,
        ], 201);
    }

    public function storeEpdPresentation(StoreEpdPresentationLeadRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Заявка принята.'], 201);
        }

        $roleLabels = [
            'expeditor' => 'Экспедитор',
            'carrier' => 'Перевозчик',
            'shipper' => 'Грузоотправитель',
        ];
        $role = $request->string('role')->toString();

        $lead = LandingLead::query()->create([
            'type' => LandingLead::TYPE_EPD_PRESENTATION,
            'status' => LandingLead::STATUS_NEW,
            'name' => $request->string('contact')->toString(),
            'phone' => $request->string('phone')->toString(),
            'quiz_answers' => [
                ['question' => 'Компания', 'answer' => $request->string('company')->toString()],
                ['question' => 'ИНН', 'answer' => $request->string('inn')->toString()],
                ['question' => 'Кто вы', 'answer' => $roleLabels[$role]],
                ['question' => 'Система формирования документов', 'answer' => $request->string('document_system')->toString()],
                ['question' => 'Контактное лицо', 'answer' => $request->string('contact')->toString()],
                ['question' => 'Телефон для связи', 'answer' => $request->string('phone')->toString()],
            ],
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Заявка принята. Мы свяжемся с вами для согласования презентации.',
            'id' => $lead->id,
        ], 201);
    }
}
