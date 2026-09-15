@extends('community.layout')
@section('title', ($post->exists ? 'Редактирование темы' : 'Новая тема').' — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell community-submit-shell">
    <header class="community-submit-heading">
        <a class="community-back" href="{{ $post->exists ? $post->getUrl() : route('community.index') }}"><x-community.icon name="arrow-left" />Назад</a>
        <h1>{{ $post->exists ? 'Редактировать тему' : 'Создать тему' }}</h1>
    </header>

    <form method="POST" action="{{ $post->exists ? route('community.posts.update', $post) : route('community.posts.store') }}" class="community-form community-submit-form" enctype="multipart/form-data" data-community-composer>
        <div class="community-submit-form__top">
            @csrf
            @if ($post->exists) @method('PUT') @endif
            <label class="community-submit-category">
                <span class="community-composer-sr-only">Рубрика</span>
                <select name="community_category_id" required aria-label="Выберите рубрику">
                    <option value="" disabled @selected(! old('community_category_id', $post->community_category_id))>Выберите рубрику</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('community_category_id', $post->community_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="community-submit-editor">
            <label class="community-composer-sr-only" for="community-post-title">Заголовок</label>
            <input id="community-post-title" class="community-submit-editor__title" name="title" value="{{ old('title', $post->title) }}" maxlength="180" required placeholder="Заголовок">

            <div class="community-submit-editor__body" data-rich-editor>
                <label class="community-composer-sr-only" for="community-post-body">Текст публикации</label>
                <textarea id="community-post-body" name="body_markdown" rows="10" maxlength="20000" placeholder="Текст публикации (необязательно)">{{ old('body_markdown', $post->body_markdown) }}</textarea>
                <div class="community-rich-editor__surface" data-rich-editor-surface hidden></div>
                @include('community.comments._format_toolbar')
            </div>

            <div class="community-submit-attachments">
                <details class="community-submit-extra" @if(old('external_url', $post->external_url)) open @endif>
                    <summary><x-community.icon name="link" size="17" />Добавить ссылку</summary>
                    <label>
                        <span class="community-composer-sr-only">Ссылка</span>
                        <input type="url" name="external_url" value="{{ old('external_url', $post->external_url) }}" maxlength="2048" placeholder="https://example.ru/article">
                    </label>
                </details>
                <div class="community-submit-photos">
                    <button type="button" data-composer-photo-trigger title="Добавить фото — до 3 файлов JPEG, PNG или WebP"><x-community.icon name="photo-up" size="17" />Добавить фото</button>
                    <span class="community-comment-composer__file-count" data-composer-file-count hidden></span>
                    @include('community.photos._editor', ['existingPhotos' => $post->exists ? $post->photos : collect(), 'compact' => true])
                </div>
            </div>

            <footer class="community-submit-actions">
                <a class="btn btn--ghost" href="{{ $post->exists ? $post->getUrl() : route('community.index') }}">Отменить</a>
                <button class="btn btn--primary" type="submit">{{ $post->exists ? 'Сохранить' : 'Опубликовать' }}</button>
            </footer>
        </div>
    </form>
</div>
@endsection
