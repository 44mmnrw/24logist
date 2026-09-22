@extends('community.layout')
@section('content')
<div class="landing-shell community-layout community-topic-layout">
    <div class="community-topic-column">
    <article class="community-topic">
        <header>
            <div class="community-author-line">
                @if ($post->author)<a class="community-avatar-link" href="{{ route('community.profile', $post->author) }}"><x-community.avatar :user="$post->author" size="md" /></a>@endif
                <div class="community-meta">
                    @if ($post->author)<a href="{{ route('community.profile', $post->author) }}" title="{{ '@'.$post->author->username }}">{{ $post->author->displayName() }}</a>@else<span>[удалён]</span>@endif
                    @if ($post->author?->transportRoleLabel())<span class="community-author-flair">{{ $post->author->transportRoleLabel() }}</span>@endif
                    <span>•</span><time>{{ \App\Support\CommunityDate::relative($post->published_at) }}</time>
                    @if ($post->edited_at)<span>• изменено</span>@endif
                </div>
            </div>
            <h1>{{ $communitySeo['h1'] }}</h1>
            @if ($post->accepted_comment_id)<span class="community-badge community-badge--resolved">Есть решение</span>@endif
            <div class="community-topic__labels">
                <a class="community-category-pill" href="{{ route('community.categories.show', $post->category) }}">{{ $post->category->name }}</a>
            </div>
        </header>
        <div class="community-topic__content">
            @if ($post->external_url)
                <a class="community-link-topic" href="{{ $post->external_url }}" rel="ugc nofollow noopener" target="_blank"><span>Открыть ссылку: {{ parse_url($post->external_url, PHP_URL_HOST) }}</span><x-community.icon name="external-link" size="17" /></a>
            @else
                <div class="community-markdown">{!! $post->body_html !!}</div>
            @endif
            @include('community.photos._gallery', ['photos' => $post->photos])
        </div>
        <div class="community-topic__actions">
            @include('community.shared._vote', ['type' => 'post', 'target' => $post, 'currentVote' => $postVote, 'variant' => 'inline'])
            @include('community.shared._awards', ['type' => 'post', 'target' => $post, 'social' => $postSocial])
            @include('community.shared._reactions', ['type' => 'post', 'target' => $post, 'social' => $postSocial])
            <a class="community-action-chip community-action-chip--comments" href="#comments"><x-community.icon name="message-circle" size="16" />{{ $post->comments_count }} {{ \App\Support\CommunityText::comments($post->comments_count) }}</a>
            <button class="community-action-chip community-action-chip--share" type="button" data-share-url="{{ $post->getUrl() }}"><x-community.icon name="share-3" size="16" /><span data-share-label>Поделиться</span></button>
            @auth('community')
                @if ($post->accepted_comment_id && ($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator()))
                    <form method="POST" action="{{ route('community.posts.clear_answer', $post) }}">@csrf @method('DELETE')<button class="community-action-chip" type="submit">Снять решение</button></form>
                @endif
                @if (auth('community')->user()->isOnboarded())
                    <form method="POST" action="{{ $subscribed ? route('community.posts.unsubscribe', $post) : route('community.posts.subscribe', $post) }}">
                        @csrf
                        @if ($subscribed)@method('DELETE')@endif
                        <button class="community-action-chip" type="submit">{{ $subscribed ? 'Отписаться от темы' : 'Следить за ответами' }}</button>
                    </form>
                @endif
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
                @include('community.comments._comment', ['comment' => $comment, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes, 'commentSocial' => $commentSocial])
            @empty
                <div class="community-empty">Пока нет комментариев. Начните обсуждение.</div>
            @endforelse
        </div>
        <div class="community-pagination">{{ $roots->links() }}</div>
    </section>
    </div>

    @php($communityAboutCard = app(\App\Services\SiteSettingsService::class)->communityAboutCard())
    <aside class="community-sidebar community-topic-sidebar" aria-label="О сообществе">
        @if ($communityAboutCard['enabled'])
            <div class="community-side-card community-about-card">
                <span class="community-side-card__eyebrow">{{ $communityAboutCard['eyebrow'] }}</span>
                <h2>{{ $communityAboutCard['title'] }}</h2>
                <p>{{ $communityAboutCard['description'] }}</p>
                <dl class="community-about-card__stats">
                    <div><dt>{{ number_format($communityStats['members'], 0, ',', ' ') }}</dt><dd>{{ $communityAboutCard['members_label'] }}</dd></div>
                    <div><dt>{{ number_format($communityStats['topics'], 0, ',', ' ') }}</dt><dd>{{ $communityAboutCard['topics_label'] }}</dd></div>
                </dl>
                <a class="community-side-card__link" href="{{ route('community.index') }}">{{ $communityAboutCard['button_text'] }}</a>
            </div>
        @endif
        <div class="community-side-card community-rules">
            <h2>Правила</h2>
            <ol>
                <li>Уважайте собеседников.</li>
                <li>Не публикуйте рекламу и персональные данные.</li>
                <li>Подкрепляйте профессиональные советы фактами.</li>
            </ol>
        </div>
    </aside>

    @auth('community')
        @include('community.shared._report_dialog')
        @include('community.shared._award_dialog')
        <dialog class="community-confirm-dialog" data-comment-delete-dialog aria-labelledby="community-comment-delete-title">
            <div class="community-confirm-dialog__icon" aria-hidden="true"><x-community.icon name="trash" size="22" /></div>
            <div class="community-confirm-dialog__content">
                <h2 id="community-comment-delete-title">Удалить комментарий?</h2>
                <p>Комментарий будет удалён. Отменить это действие не получится.</p>
            </div>
            <button class="community-confirm-dialog__close" type="button" data-comment-delete-cancel aria-label="Закрыть"><x-community.icon name="x" size="18" /></button>
            <div class="community-confirm-dialog__actions">
                <button class="btn btn--ghost btn--sm" type="button" data-comment-delete-cancel>Отмена</button>
                <button class="btn btn--sm community-confirm-dialog__danger" type="button" data-comment-delete-confirm>Удалить</button>
            </div>
        </dialog>
    @endauth
</div>
@endsection
