<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactLeadRequest;
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
}
