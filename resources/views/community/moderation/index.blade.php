@extends('community.layout')
@section('title', 'Модерация — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <h1>Очередь модерации</h1>
    <div class="community-report-list">
        @forelse($reports as $report)
            <article class="community-report-card"><div><strong>{{ $report->targetLabel() }} #{{ $report->target_id }}</strong><span>{{ $report->reasonLabel() }}</span><p>{{ $report->details }}</p><time>{{ \App\Support\CommunityDate::relative($report->created_at) }}</time></div>
                <form method="POST" action="{{ route('community.moderation.act', $report) }}" class="community-form">@csrf<select name="action" required><option value="dismiss">Отклонить жалобу</option><option value="hide">Скрыть</option><option value="restore">Восстановить</option><option value="lock">Закрыть тему</option><option value="unlock">Открыть тему</option><option value="pin">Закрепить</option><option value="unpin">Открепить</option><option value="suspend_1">Ограничить на 1 день</option><option value="suspend_7">Ограничить на 7 дней</option><option value="suspend_30">Ограничить на 30 дней</option><option value="ban">Заблокировать</option></select><input name="reason" maxlength="1000" placeholder="Комментарий модератора"><button class="btn btn--primary btn--sm">Применить</button></form>
            </article>
        @empty<div class="community-empty">Открытых жалоб нет.</div>@endforelse
    </div>
    <div class="community-pagination">{{ $reports->links() }}</div>
</div>
@endsection
