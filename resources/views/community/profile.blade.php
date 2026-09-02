@extends('community.layout')
@section('title', $user->displayName().' — Сообщество 24Logist')
@section('robots', 'noindex, follow')

@section('content')
<div class="landing-shell community-layout">
    <section class="community-feed">
        <header class="community-profile-header">
            <x-community.avatar :user="$user" size="lg" />
            <div class="community-profile-details">
                <h1>{{ $user->displayName() }}</h1>
                <p class="community-profile-handle">{{ '@'.$user->username }}</p>
                <p>
                    @if ($user->transportRoleLabel())<span>{{ $user->transportRoleLabel() }}</span> · @endif
                    {{ $user->karma }} рейтинга · в сообществе с {{ \App\Support\CommunityDate::monthYear($user->onboarded_at) }}
                </p>
                @if ($user->bio)<p class="community-profile-bio">{{ $user->bio }}</p>@endif
            </div>
        </header>
        <h2>Темы пользователя</h2>
        <div class="community-posts">@forelse($posts as $post)@include('community.posts._card', ['post' => $post])@empty<div class="community-empty">Пока нет опубликованных тем.</div>@endforelse</div>
        <div class="community-pagination">{{ $posts->links() }}</div>
    </section>
</div>
@endsection
