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
        <header>
            <div class="community-author-line">
                @if ($post->author)<a class="community-avatar-link" href="{{ route('community.profile', $post->author) }}"><x-community.avatar :user="$post->author" size="md" /></a>@endif
                <div class="community-meta">
                    <a class="community-category-pill" href="{{ route('community.categories.show', $post->category) }}">{{ $post->category->name }}</a>
                    <span>•</span>
                    @if ($post->author)<a href="{{ route('community.profile', $post->author) }}" title="{{ '@'.$post->author->username }}">{{ $post->author->displayName() }}</a>@else<span>[удалён]</span>@endif
                    <span>•</span><time>{{ \App\Support\CommunityDate::relative($post->published_at) }}</time>
                    @if ($post->edited_at)<span>• изменено</span>@endif
                </div>
            </div>
            <h1>{{ $post->title }}</h1>
        </header>
        <div class="community-topic__content">
            @if ($post->external_url)
                <a class="community-link-topic" href="{{ $post->external_url }}" rel="ugc nofollow noopener" target="_blank">Открыть ссылку: {{ parse_url($post->external_url, PHP_URL_HOST) }} ↗</a>
            @else
                <div class="community-markdown">{!! $post->body_html !!}</div>
            @endif
        </div>
        <div class="community-topic__actions">
            @include('community.shared._vote', ['type' => 'post', 'target' => $post, 'currentVote' => $postVote, 'variant' => 'inline'])
            <a class="community-action-chip community-action-chip--comments" href="#comments"><span aria-hidden="true">◯</span>{{ $post->comments_count }} {{ trans_choice('комментарий|комментария|комментариев', $post->comments_count) }}</a>
            <button class="community-action-chip community-action-chip--share" type="button" data-share-url="{{ $post->getUrl() }}"><span aria-hidden="true">↗</span><span data-share-label>Поделиться</span></button>
            @auth('community')
                @if ($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())
                    <a class="community-action-chip" href="{{ route('community.posts.edit', $post) }}">Изменить</a>
                    <form method="POST" action="{{ route('community.posts.destroy', $post) }}" onsubmit="return confirm('Удалить тему?')">@csrf @method('DELETE')<button class="community-action-chip">Удалить</button></form>
                @endif
                <button class="community-action-chip" type="button" data-report-open data-report-type="post" data-report-id="{{ $post->id }}">Пожаловаться</button>
            @endauth
        </div>
    </article>

    <section id="comments" class="community-comments">
        @if ($post->locked_at)
            <div class="community-notice">Обсуждение закрыто модератором.</div>
        @elseif (auth('community')->check() && auth('community')->user()->isOnboarded())
            @include('community.comments._form', ['post' => $post, 'parent' => null])
        @else
            <div class="community-notice"><a href="{{ route('community.login') }}">Войдите</a>, чтобы оставить комментарий.</div>
        @endif

        <div class="community-comments__toolbar">
            <h2>Комментарии <span>{{ $post->comments_count }}</span></h2>
            <nav aria-label="Сортировка комментариев">
                <span>Сначала:</span>
                <a @class(['is-active' => $commentSort === 'best']) href="{{ request()->fullUrlWithQuery(['comment_sort' => 'best', 'page' => null]).'#comments' }}">лучшие</a>
                <a @class(['is-active' => $commentSort === 'new']) href="{{ request()->fullUrlWithQuery(['comment_sort' => 'new', 'page' => null]).'#comments' }}">новые</a>
                <a @class(['is-active' => $commentSort === 'old']) href="{{ request()->fullUrlWithQuery(['comment_sort' => 'old', 'page' => null]).'#comments' }}">старые</a>
            </nav>
        </div>

        <div class="community-comment-list">
            @forelse ($roots as $comment)
                @include('community.comments._comment', ['comment' => $comment, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes])
            @empty
                <div class="community-empty">Пока нет комментариев. Начните обсуждение.</div>
            @endforelse
        </div>
        <div class="community-pagination">{{ $roots->links() }}</div>
    </section>

    @auth('community')
        @include('community.shared._report_dialog')
    @endauth
</div>
@endsection
