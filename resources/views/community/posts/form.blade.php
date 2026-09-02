@extends('community.layout')
@section('title', ($post->exists ? 'Редактирование темы' : 'Новая тема').' — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <a class="community-back" href="{{ $post->exists ? $post->getUrl() : route('community.index') }}">← Назад</a>
    <div class="community-form-card">
        <h1>{{ $post->exists ? 'Редактировать тему' : 'Создать тему' }}</h1>
        <form method="POST" action="{{ $post->exists ? route('community.posts.update', $post) : route('community.posts.store') }}" class="community-form">
            @csrf
            @if ($post->exists) @method('PUT') @endif
            <label>Рубрика<select name="community_category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('community_category_id', $post->community_category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label>Заголовок<input name="title" value="{{ old('title', $post->title) }}" maxlength="180" required></label>
            <fieldset><legend>Выберите один формат</legend>
                <label>Текст в Markdown<textarea name="body_markdown" rows="12" maxlength="20000" placeholder="Опишите вопрос или поделитесь опытом…">{{ old('body_markdown', $post->body_markdown) }}</textarea></label>
                <div class="community-form__or">или</div>
                <label>Ссылка<input type="url" name="external_url" value="{{ old('external_url', $post->external_url) }}" maxlength="2048" placeholder="https://example.ru/article"></label>
            </fieldset>
            <button class="btn btn--primary" type="submit">{{ $post->exists ? 'Сохранить' : 'Опубликовать' }}</button>
        </form>
    </div>
</div>
@endsection
