<x-mail::message>
# Новая заявка с сайта

**Тип:** {{ $lead->typeLabel() }}

**Имя:** {{ $lead->name }}

**Телефон:** {{ $lead->phone }}

@if (filled($lead->email))
**Email:** {{ $lead->email }}
@endif

@if ($lead->type === \App\Models\LandingLead::TYPE_CONTACT && filled($lead->message))
**Сообщение:**

{{ $lead->message }}
@endif

@if ($lead->type === \App\Models\LandingLead::TYPE_QUIZ)
@if (filled($lead->recommended_plan_title))
**Рекомендованный тариф:** {{ $lead->recommended_plan_title }}
@endif

@if (filled($lead->quiz_answers))
**Ответы квиза:**

@foreach ($lead->quiz_answers as $row)
- **{{ $row['question'] ?? 'Вопрос' }}:** {{ $row['answer'] ?? '—' }}
@endforeach
@endif
@endif

@if (filled($lead->source_url))
**Страница:** {{ $lead->source_url }}
@endif

**Дата:** {{ $lead->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}

<x-mail::button :url="$adminUrl">
Открыть в админке
</x-mail::button>

С уважением,<br>
{{ config('app.name') }}
</x-mail::message>
