<article class="community-post-card">
    <div class="community-score" aria-label="Рейтинг {{ $post->score }}"><strong>{{ $post->score }}</strong><span>голосов</span></div>
    <div class="community-post-card__body">
        <div class="community-meta">
            <a href="{{ route('community.categories.show', $post->category) }}">{{ $post->category->name }}</a>
            <span>•</span>
            @if ($post->author)<a href="{{ route('community.profile', $post->author) }}">{{ '@'.$post->author->username }}</a>@else<span>[удалён]</span>@endif
            <span>•</span><time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->diffForHumans() }}</time>
        </div>
        <h2><a href="{{ $post->getUrl() }}">{{ $post->title }}</a></h2>
        @if ($post->external_url)
            <a class="community-external" href="{{ $post->external_url }}" rel="ugc nofollow noopener" target="_blank">{{ parse_url($post->external_url, PHP_URL_HOST) }} ↗</a>
        @elseif ($post->body_markdown)
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->body_html), 240) }}</p>
        @endif
        <div class="community-post-card__footer">
            <a href="{{ $post->getUrl() }}#comments">{{ $post->comments_count }} {{ trans_choice('комментарий|комментария|комментариев', $post->comments_count) }}</a>
            @if ($post->is_pinned)<span class="community-badge">Закреплено</span>@endif
            @if ($post->locked_at)<span class="community-badge">Закрыто</span>@endif
        </div>
    </div>
</article>
