<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommercialOfferLeadRequest;
use App\Http\Requests\StoreContactLeadRequest;
use App\Http\Requests\StoreEpdPresentationLeadRequest;
use App\Http\Requests\StoreQuizLeadRequest;
use App\Models\LandingBlock;
use App\Models\LandingLead;
use App\Support\LandingLeadQuizAnswers;
use Illuminate\Http\JsonResponse;

class LandingLeadController extends Controller
{
    public function storeCommercialOffer(StoreCommercialOfferLeadRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Заявка принята.'], 201);
        }

        $plan = LandingBlock::query()
            ->where('section_slug', 'pricing_wide')
            ->where('block_type', 'plan')
            ->where('is_active', true)
            ->first();
        $extra = is_array($plan?->extra) ? $plan->extra : [];
        $minimumUsers = max(1, (int) ($extra['users_min'] ?? 1));
        $maximumUsers = min(500, max($minimumUsers, (int) ($extra['users_max'] ?? 500)));
        $users = min($maximumUsers, max($minimumUsers, $request->integer('users')));
        $optionIds = collect($request->validated('option_ids', []))->map(fn ($id): int => (int) $id)->all();
        $options = $plan
            ? $plan->children()->where('block_type', 'paid_option')->where('is_active', true)->whereIn('id', $optionIds)->get()
            : collect();
        $total = (max(0, (int) ($plan?->price ?? 0)) * $users) + $options->sum(fn (LandingBlock $option): int => max(0, (int) $option->price));
        $currencySuffix = trim((string) ($extra['currency_suffix'] ?? '₽/мес'));

        $lead = LandingLead::query()->create([
            'type' => LandingLead::TYPE_COMMERCIAL_OFFER,
            'status' => LandingLead::STATUS_NEW,
            'name' => $request->string('name')->toString(),
            'phone' => $request->string('phone')->toString(),
            'email' => $request->string('email')->toString(),
            'quiz_answers' => [
                ['question' => 'Название компании', 'answer' => $request->string('company')->toString()],
                ['question' => 'ИНН', 'answer' => $request->string('inn')->toString()],
                ['question' => 'Количество пользователей', 'answer' => (string) $users],
                ['question' => 'Дополнительные функции', 'answer' => $options->pluck('title')->implode(', ') ?: 'Не выбраны'],
                ['question' => 'Расчётная стоимость', 'answer' => number_format($total, 0, ',', ' ').($currencySuffix !== '' ? ' '.$currencySuffix : '')],
            ],
            'recommended_plan_id' => $plan?->id,
            'recommended_plan_title' => $plan?->title,
            'source_url' => $request->headers->get('referer'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Заявка принята. Мы подготовим коммерческое предложение и свяжемся с вами.',
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

    public function storeContact(StoreContactLeadRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Сообщение отправлено.'], 201);
        }

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
