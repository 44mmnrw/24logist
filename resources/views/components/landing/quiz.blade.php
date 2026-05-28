@php
    $section = $landing->section('quiz');
@endphp

@if ($section)
<section class="quiz-section" id="quiz">
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

        $quizPayload = [
            'questions' => $questions->all(),
            'finish' => [
                'title' => $extra['finish_title'] ?? 'Куда прислать расчёт?',
                'description' => $extra['finish_description'] ?? 'Оставьте контакты — пришлём подходящий тариф и расчёт в рабочее время.',
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
