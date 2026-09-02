<form method="POST" action="{{ route('community.comments.store', $post) }}" class="community-comment-form">
    @csrf
    @if ($parent)<input type="hidden" name="parent_id" value="{{ $parent->id }}">@endif
    <textarea name="body_markdown" maxlength="5000" rows="{{ $parent ? 3 : 5 }}" required placeholder="{{ $parent ? 'Ваш ответ…' : 'Присоединитесь к обсуждению…' }}"></textarea>
    <div><small>Поддерживается Markdown</small><button class="btn btn--primary btn--sm" type="submit">{{ $parent ? 'Ответить' : 'Опубликовать' }}</button></div>
</form>
