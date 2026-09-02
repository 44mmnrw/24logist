<form method="POST" action="{{ route('community.comments.store', $post) }}" @class(['community-comment-form', 'community-comment-form--root' => ! $parent])>
    @csrf
    @if ($parent)<input type="hidden" name="parent_id" value="{{ $parent->id }}">@endif
    <textarea name="body_markdown" maxlength="5000" rows="{{ $parent ? 3 : 1 }}" required placeholder="{{ $parent ? 'Ваш ответ…' : 'Вступить в беседу' }}"></textarea>
    <div><small>До 5 000 символов</small><button class="btn btn--primary btn--sm" type="submit">{{ $parent ? 'Ответить' : 'Отправить' }}</button></div>
</form>
