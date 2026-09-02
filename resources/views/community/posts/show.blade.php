@extends('community.layout')
@section('title', $post->title.' — Сообщество 24Logist')
@section('description', \Illuminate\Support\Str::limit(strip_tags($post->body_html ?: $post->external_url), 160))
@section('canonical', $post->getUrl())

@push('structured-data')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'DiscussionForumPosting',
    'headline' => $post->title, 'url' => $post->getUrl(),
    'datePublished' => $post->published_at?->toIso8601String(),
    'dateModified' => $post->edited_at?->toIso8601String() ?: $post->updated_at?->toIso8601String(),
    'author' => ['@type' => 'Person', 'name' => $post->author?->username ?: '[удалён]'],
    'commentCount' => $post->comments_count, 'interactionStatistic' => ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/LikeAction', 'userInteractionCount' => $post->score],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<div class="landing-shell community-topic-layout">
    <article class="community-topic">
        <a class="community-back" href="{{ route('community.categories.show', $post->category) }}">← {{ $post->category->name }}</a>
        <header>
            <div class="community-meta">
                @if ($post->author)<a href="{{ route('community.profile', $post->author) }}">{{ '@'.$post->author->username }}</a>@else<span>[удалён]</span>@endif
                <span>•</span><time>{{ $post->published_at?->diffForHumans() }}</time>
                @if ($post->edited_at)<span>• изменено</span>@endif
            </div>
            <h1>{{ $post->title }}</h1>
        </header>
        <div class="community-topic__row">
            @include('community.shared._vote', ['type' => 'post', 'target' => $post, 'currentVote' => $postVote])
            <div class="community-topic__content">
                @if ($post->external_url)
                    <a class="community-link-topic" href="{{ $post->external_url }}" rel="ugc nofollow noopener" target="_blank">Открыть ссылку: {{ parse_url($post->external_url, PHP_URL_HOST) }} ↗</a>
                @else
                    <div class="community-markdown">{!! $post->body_html !!}</div>
                @endif
            </div>
        </div>
        <div class="community-topic__actions">
            @auth('community')
                @if ($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())
                    <a href="{{ route('community.posts.edit', $post) }}">Редактировать</a>
                    <form method="POST" action="{{ route('community.posts.destroy', $post) }}" onsubmit="return confirm('Удалить тему?')">@csrf @method('DELETE')<button>Удалить</button></form>
                @endif
                <details><summary>Пожаловаться</summary>@include('community.shared._report', ['type' => 'post', 'targetId' => $post->id])</details>
            @endauth
        </div>
    </article>

    <section id="comments" class="community-comments">
        <h2>Комментарии <span>{{ $post->comments_count }}</span></h2>
        @if ($post->locked_at)
            <div class="community-notice">Обсуждение закрыто модератором.</div>
        @elseif (auth('community')->check() && auth('community')->user()->isOnboarded())
            @include('community.comments._form', ['post' => $post, 'parent' => null])
        @else
            <div class="community-notice"><a href="{{ route('community.login') }}">Войдите</a>, чтобы оставить комментарий.</div>
        @endif

        <div class="community-comment-list">
            @forelse ($roots as $comment)
                @include('community.comments._comment', ['comment' => $comment, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes])
            @empty
                <div class="community-empty">Пока нет комментариев. Начните обсуждение.</div>
            @endforelse
        </div>
        <div class="community-pagination">{{ $roots->links() }}</div>
    </section>
</div>
@endsection
