@extends('community.layout')
@section('title', '@'.$user->username.' — Сообщество 24Logist')
@section('robots', 'noindex, follow')

@section('content')
<div class="landing-shell community-layout">
    <section class="community-feed">
        <header class="community-profile-header"><div class="community-avatar">{{ mb_strtoupper(mb_substr($user->username, 0, 1)) }}</div><div><h1>{{ '@'.$user->username }}</h1><p>{{ $user->karma }} рейтинга · в сообществе с {{ $user->onboarded_at?->translatedFormat('F Y') }}</p></div></header>
        <h2>Темы пользователя</h2>
        <div class="community-posts">@forelse($posts as $post)@include('community.posts._card', ['post' => $post])@empty<div class="community-empty">Пока нет опубликованных тем.</div>@endforelse</div>
        <div class="community-pagination">{{ $posts->links() }}</div>
    </section>
</div>
@endsection
