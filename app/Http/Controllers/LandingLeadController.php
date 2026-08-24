<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactLeadRequest;
use App\Http\Requests\StoreEpdPresentationLeadRequest;
use App\Http\Requests\StoreQuizLeadRequest;
use App\Models\LandingLead;
use App\Support\LandingLeadQuizAnswers;
use Illuminate\Http\JsonResponse;

class LandingLeadController extends Controller
{
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
