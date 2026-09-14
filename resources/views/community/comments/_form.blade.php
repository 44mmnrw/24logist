<form method="POST" action="{{ route('community.comments.store', $post) }}" enctype="multipart/form-data" @class(['community-comment-form', 'community-comment-form--root' => ! $parent]) data-community-composer>
    @csrf
    @if ($parent)<input type="hidden" name="parent_id" value="{{ $parent->id }}">@endif
    <div class="community-comment-composer" data-community-dropzone data-rich-editor>
        @include('community.comments._format_toolbar')
        <label class="community-composer-sr-only" for="community-comment-body-{{ $parent?->id ?? 'root' }}">{{ $parent ? 'Ваш ответ' : 'Вступить в беседу' }}</label>
        <textarea id="community-comment-body-{{ $parent?->id ?? 'root' }}" name="body_markdown" maxlength="5000" rows="1" placeholder="{{ $parent ? 'Ваш ответ…' : 'Вступить в беседу' }}" data-composer-textarea></textarea>
        <div class="community-rich-editor__surface" data-rich-editor-surface hidden></div>
        @include('community.photos._editor', ['compact' => true])
        <div class="community-comment-composer__toolbar">
            <div class="community-comment-composer__tools">
                <button class="community-comment-composer__tool" type="button" data-composer-photo-trigger title="Добавить фото — до 3 файлов JPEG, PNG или WebP" aria-label="Добавить фото"><x-community.icon name="photo-up" size="18" /></button>
                <span class="community-comment-composer__file-count" data-composer-file-count hidden></span>
            </div>
            <div class="community-comment-composer__actions">
                <button class="community-comment-composer__cancel" type="reset">Отменить</button>
                <button class="btn btn--primary btn--sm community-comment-composer__submit" type="submit">{{ $parent ? 'Ответить' : 'Комментарий' }}</button>
            </div>
        </div>
    </div>
</form>
