@extends('community.layout')

@section('title', ($activeCategory?->name ? $activeCategory->name.' — ' : '').'Сообщество 24Logist')
@section('description', $activeCategory?->description ?: 'Практические обсуждения перевозок, ЭДО и цифровой логистики.')

@section('content')
<div class="landing-shell community-layout">
    <section class="community-feed">
        <header class="community-hero">
            <div>
                <span class="section-kicker">Сообщество 24Logist</span>
                <h1>{{ $activeCategory?->name ?: 'Обсуждаем логистику вместе' }}</h1>
                <p>{{ $activeCategory?->description ?: 'Задавайте вопросы, делитесь опытом и находите практические решения.' }}</p>
            </div>
            @auth('community')
                @if (auth('community')->user()->isOnboarded())
                    <a class="btn btn--primary" href="{{ route('community.posts.create') }}">Создать тему</a>
                @endif
            @else
                <a class="btn btn--primary" href="{{ route('community.login') }}">Присоединиться</a>
            @endauth
        </header>

        <form class="community-search" method="GET" action="{{ $activeCategory ? route('community.categories.show', $activeCategory) : route('community.index') }}" role="search">
            <label for="community-search-input" class="sr-only">Поиск по темам</label>
            <input id="community-search-input" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Найти тему или ответ" autocomplete="off">
            <button type="submit">Найти</button>
            @if ($search !== '')<a href="{{ $activeCategory ? route('community.categories.show', $activeCategory) : route('community.index') }}">Сбросить</a>@endif
        </form>

        <nav class="community-sort" aria-label="Сортировка тем">
            <span class="community-sort__label">Сортировка:</span>
            <a @class(['is-active' => $sort === 'hot']) href="{{ request()->fullUrlWithQuery(['sort' => 'hot', 'period' => null, 'page' => null]) }}"><x-community.icon name="flame" />Актуальное</a>
            <a @class(['is-active' => $sort === 'new']) href="{{ request()->fullUrlWithQuery(['sort' => 'new', 'period' => null, 'page' => null]) }}"><x-community.icon name="sparkles" />Новое</a>
            <a @class(['is-active' => $sort === 'top']) href="{{ request()->fullUrlWithQuery(['sort' => 'top', 'page' => null]) }}"><x-community.icon name="trophy" />Лучшее</a>
            <a @class(['is-active' => $sort === 'unanswered']) href="{{ request()->fullUrlWithQuery(['sort' => 'unanswered', 'period' => null, 'page' => null]) }}"><x-community.icon name="message-circle" />Ждут ответа</a>
            @if ($sort === 'top')
                <select aria-label="Период" onchange="location.href=this.value">
                    @foreach (['day' => 'Сутки', 'week' => 'Неделя', 'month' => 'Месяц', 'all' => 'Всё время'] as $value => $label)
                        <option value="{{ request()->fullUrlWithQuery(['period' => $value, 'page' => null]) }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endif
        </nav>

        <div class="community-posts">
            @forelse ($posts as $post)
                @include('community.posts._card', ['post' => $post])
            @empty
                <div class="community-empty"><h2>{{ $search !== '' ? 'Ничего не найдено' : 'Здесь пока тихо' }}</h2><p>{{ $search !== '' ? 'Попробуйте другой запрос.' : 'Станьте автором первой темы в этой рубрике.' }}</p></div>
            @endforelse
        </div>
        <div class="community-pagination">{{ $posts->links() }}</div>
        @include('community.shared._award_dialog')
    </section>

    <aside class="community-sidebar">
        <div class="community-side-card">
            <h2>Рубрики</h2>
            <a @class(['is-active' => ! $activeCategory]) href="{{ route('community.index') }}"><span>Все обсуждения</span></a>
            @foreach ($categories as $category)
                <a @class(['is-active' => $activeCategory?->is($category)]) href="{{ route('community.categories.show', $category) }}">
                    <span>{{ $category->name }}</span><small>{{ $category->posts_count }}</small>
                </a>
            @endforeach
        </div>
        <div class="community-side-card community-rules">
            <h2>Коротко о правилах</h2>
            <ol><li>Уважайте собеседников.</li><li>Не публикуйте рекламу и персональные данные.</li><li>Подкрепляйте профессиональные советы фактами.</li></ol>
        </div>
    </aside>
</div>
@endsection
