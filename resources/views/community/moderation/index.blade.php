@extends('community.layout')
@section('title', 'Модерация — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <h1>Очередь модерации</h1>
    <div class="community-report-list">
        @forelse($reports as $report)
            @php
                $target = $targets[$report->target_type][$report->target_id] ?? null;
                $post = $target instanceof \App\Models\CommunityPost ? $target : ($target instanceof \App\Models\CommunityComment ? $target->post : null);
                $targetUrl = $post && ! $post->trashed() && $post->status === 'published'
                    ? $post->getUrl().($target instanceof \App\Models\CommunityComment ? '#comment-'.$target->id : '')
                    : null;
                $canAct = $target && ! $target->trashed() && $target->status !== 'deleted';
            @endphp
            <article class="community-report-card">
                <div>
                    <strong>{{ $report->targetLabel() }} #{{ $report->target_id }}</strong>
                    @if ($targetUrl)<a href="{{ $targetUrl }}" target="_blank" rel="noopener">Открыть материал</a>@endif
                    @if ($target instanceof \App\Models\CommunityPost)<p>{{ $target->title }}</p>@endif
                    @if ($target instanceof \App\Models\CommunityComment)<p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $target->body_html), 200) }}</p>@endif
                    <span>{{ $report->reasonLabel() }}</span>
                    <p>{{ $report->details }}</p>
                    <time>{{ \App\Support\CommunityDate::relative($report->created_at) }}</time>
                </div>
                <form method="POST" action="{{ route('community.moderation.act', $report) }}" class="community-form">
                    @csrf
                    <select name="action" required>
                        <option value="dismiss">Отклонить жалобу</option>
                        @if ($canAct && $target->status === 'published')<option value="hide">Скрыть</option>@endif
                        @if ($canAct && $target->status === 'hidden')<option value="restore">Восстановить</option>@endif
                        @if ($canAct && $target instanceof \App\Models\CommunityPost)
                            <option value="{{ $target->locked_at ? 'unlock' : 'lock' }}">{{ $target->locked_at ? 'Открыть тему' : 'Закрыть тему' }}</option>
                            <option value="{{ $target->is_pinned ? 'unpin' : 'pin' }}">{{ $target->is_pinned ? 'Открепить' : 'Закрепить' }}</option>
                        @endif
                        @if ($canAct && $target->community_user_id)
                            <option value="suspend_1">Ограничить на 1 день</option>
                            <option value="suspend_7">Ограничить на 7 дней</option>
                            <option value="suspend_30">Ограничить на 30 дней</option>
                            <option value="ban">Заблокировать</option>
                        @endif
                    </select>
                    <input name="reason" maxlength="1000" placeholder="Комментарий модератора">
                    <button class="btn btn--primary btn--sm">Применить</button>
                </form>
            </article>
        @empty
            <div class="community-empty">Открытых жалоб нет.</div>
        @endforelse
    </div>
    <div class="community-pagination">{{ $reports->links() }}</div>
</div>
@endsection
