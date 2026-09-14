@php($postVotes = $postVotes ?? collect())
<article class="community-post-card">
    <a class="community-post-card__overlay" href="{{ $post->getUrl() }}" aria-labelledby="community-post-title-{{ $post->id }}"></a>
    <div class="community-post-card__vote">
        @include('community.shared._vote', ['type' => 'post', 'target' => $post, 'currentVote' => $postVotes->get($post->id)])
    </div>
    <div class="community-post-card__body">
        <div class="community-author-line">
            @if ($post->author)<a class="community-avatar-link" href="{{ route('community.profile', $post->author) }}"><x-community.avatar :user="$post->author" size="sm" /></a>@endif
            <div class="community-meta">
                <a class="community-category-pill" href="{{ route('community.categories.show', $post->category) }}">{{ $post->category->name }}</a>
                <span class="community-meta__separator">•</span>
                @if ($post->author)<a href="{{ route('community.profile', $post->author) }}" title="{{ '@'.$post->author->username }}">{{ $post->author->displayName() }}</a>@else<span>[удалён]</span>@endif
                <span class="community-meta__separator">•</span><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ \App\Support\CommunityDate::relative($post->published_at) }}</time>
                @if ($post->locked_at)<span class="community-post-card__status">Закрыто</span>@endif
                @if ($post->accepted_comment_id)<span class="community-post-card__status community-post-card__status--resolved">Есть решение</span>@endif
            </div>
            @if ($post->is_pinned)<span class="community-post-card__pinned" aria-label="Закреплено"><x-community.icon name="pin" size="14" /><span>Закреплено</span></span>@endif
        </div>
        <h2 id="community-post-title-{{ $post->id }}">{{ $post->title }}</h2>
        @if ($post->external_url)
            <a class="community-external" href="{{ $post->external_url }}" rel="ugc nofollow noopener" target="_blank"><span>{{ parse_url($post->external_url, PHP_URL_HOST) }}</span><x-community.icon name="external-link" size="15" /></a>
        @elseif ($post->body_markdown)
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->body_html), 240) }}</p>
        @endif
        @include('community.photos._gallery', ['photos' => $post->photos, 'feed' => true])
        <div class="community-post-card__footer">
            @include('community.shared._awards', ['type' => 'post', 'target' => $post, 'social' => $social[$post->id] ?? [], 'feed' => true])
            @include('community.shared._reactions', ['type' => 'post', 'target' => $post, 'social' => $social[$post->id] ?? []])
            <a class="community-action-chip community-action-chip--comments" href="{{ $post->getUrl() }}#comments" aria-label="{{ $post->comments_count }} {{ \App\Support\CommunityText::comments($post->comments_count) }}"><x-community.icon name="message-circle" size="16" /><span>{{ $post->comments_count }}</span><span class="community-action-chip__label">{{ \App\Support\CommunityText::comments($post->comments_count) }}</span></a>
            <button class="community-action-chip community-action-chip--share" type="button" data-share-url="{{ $post->getUrl() }}" aria-label="Поделиться"><x-community.icon name="share-3" size="16" /><span data-share-label>Поделиться</span></button>
        </div>
    </div>
</article>
