<article id="comment-{{ $comment->id }}" class="community-comment" style="--comment-depth: {{ $comment->depth }}">
    <div class="community-comment__body">
        @include('community.shared._vote', ['type' => 'comment', 'target' => $comment, 'currentVote' => $commentVotes->get($comment->id)])
        <div class="community-comment__content">
            <div class="community-meta">
                @if ($comment->author)<a href="{{ route('community.profile', $comment->author) }}">{{ '@'.$comment->author->username }}</a>@else<span>[удалён]</span>@endif
                <span>•</span><time>{{ $comment->created_at->diffForHumans() }}</time>@if($comment->edited_at)<span>• изменено</span>@endif
            </div>
            @if ($comment->status === 'deleted')<p class="community-deleted">Комментарий удалён автором.</p>@else<div class="community-markdown">{!! $comment->body_html !!}</div>@endif
            @auth('community')
                <div class="community-comment__actions">
                    @if ($comment->status === 'published' && !$post->locked_at && $comment->depth < config('community.limits.comment_depth') - 1)
                        <details><summary>Ответить</summary>@include('community.comments._form', ['post' => $post, 'parent' => $comment])</details>
                    @endif
                    @if ($comment->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())
                        @if ($comment->status === 'published')<details><summary>Изменить</summary><form method="POST" action="{{ route('community.comments.update', $comment) }}" class="community-comment-form">@csrf @method('PUT')<textarea name="body_markdown" maxlength="5000" rows="3" required>{{ $comment->body_markdown }}</textarea><button class="btn btn--sm">Сохранить</button></form></details>@endif
                        <form method="POST" action="{{ route('community.comments.destroy', $comment) }}">@csrf @method('DELETE')<button>Удалить</button></form>
                    @endif
                    @if ($comment->status === 'published')<details><summary>Пожаловаться</summary>@include('community.shared._report', ['type' => 'comment', 'targetId' => $comment->id])</details>@endif
                </div>
            @endauth
        </div>
    </div>
    @foreach ($children->get($comment->id, collect()) as $child)
        @include('community.comments._comment', ['comment' => $child, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes])
    @endforeach
</article>
