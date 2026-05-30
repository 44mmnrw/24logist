@php
    use App\Support\LandingLinks;
    use App\Support\LandingQuizRecommendation;

    $section = $landing->section('quiz');
@endphp

@if ($section)
<section class="quiz-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $extra = $section?->extra ?? [];
        $questions = $landing->blocks('quiz', 'question')->map(function ($question) {
            return [
                'id' => $question->id,
                'title' => $question->title,
                'options' => $question->children
                    ->where('block_type', 'option')
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($option) => [
                        'id' => $option->id,
                        'title' => $option->title,
                    ])
                    ->all(),
            ];
        })->values();

        $firstQuestion = $questions->first();

        $quizPayload = [
            'submitUrl' => route('leads.quiz.store'),
            'questions' => $questions->all(),
            'firstQuestionId' => $firstQuestion['id'] ?? null,
            'plans' => LandingQuizRecommendation::plansPayload(),
            'optionPlans' => LandingQuizRecommendation::optionPlanMap(),
            'recommendation' => [
                'title' => $extra['recommendation_title'] ?? 'Вам подходит тариф',
                'description' => $extra['recommendation_description'] ?? 'На основе вашего ответа мы подобрали оптимальный план — оставьте контакты, и мы пришлём расчёт.',
            ],
            'finish' => [
                'title' => $extra['finish_title'] ?? 'Куда прислать расчёт?',
                'description' => $extra['finish_description'] ?? 'Оставьте контакты — пришлём подходящий тариф и расчёт в рабочее время.',
                'privacyPrefix' => $extra['privacy_prefix'] ?? 'Нажимая кнопку, вы соглашаетесь с',
                'privacyLinkText' => $extra['privacy_link_text'] ?? 'политикой конфиденциальности',
                'privacyUrl' => url('/pages/privacy-policy'),
            ],
            'success' => [
                'title' => $extra['success_title'] ?? 'Спасибо!',
                'description' => $extra['success_description'] ?? 'Мы получили ваши ответы и свяжемся с вами в ближайшее время.',
            ],
            'labels' => [
                'back' => $extra['back_button_text'] ?? 'Назад',
                'next' => $extra['next_button_text'] ?? 'Далее',
                'submit' => $extra['submit_button_text'] ?? 'Получить расчёт',
                'step' => $extra['step_label'] ?? 'Шаг',
                'of' => $extra['of_label'] ?? 'из',
            ],
            'nextButtonIcon' => $extra['next_button_icon'] ?? null,
            'links' => [
                'pricing' => LandingLinks::resolve($landing->section('pricing')?->anchorLink() ?? '#pricing'),
                'finalCta' => LandingLinks::resolve($landing->section('final_cta')?->anchorLink() ?? '#final-cta'),
            ],
        ];
    @endphp

    <div class="quiz-box">
        <header class="section-head section-head--center">
            @if ($section?->kicker)
                <span class="section-kicker">{{ $section->kicker }}</span>
            @endif
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section?->description)
                <p>{{ $section->description }}</p>
            @endif
        </header>

        @if ($questions->isNotEmpty())
            <div
                class="quiz-card"
                data-landing-quiz
                data-quiz='@json($quizPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)'
            ></div>
        @else
            <div class="quiz-card quiz-card--empty">
                <p>Квиз скоро будет доступен.</p>
            </div>
        @endif
    </div>
</section>
@endif
