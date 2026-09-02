@extends('community.layout')
@section('title', 'Уведомления — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <div class="community-page-heading"><h1>Уведомления</h1><form method="POST" action="{{ route('community.notifications.read_all') }}">@csrf<button>Отметить все прочитанными</button></form></div>
    <div class="community-notification-list">
        @forelse($notifications as $notification)
            <a @class(['community-notification', 'is-unread' => !$notification->read_at]) href="{{ route('community.notifications.read', $notification) }}">
                <strong>{{ $notification->data['message'] ?? 'Новое уведомление' }}</strong>
                <time>{{ $notification->created_at->diffForHumans() }}</time>
            </a>
        @empty<div class="community-empty">Новых уведомлений нет.</div>@endforelse
    </div>
    <div class="community-pagination">{{ $notifications->links() }}</div>
</div>
@endsection
