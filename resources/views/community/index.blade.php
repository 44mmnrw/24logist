@extends('community.layout')

@section('title', ($activeCategory?->name ? $activeCategory->name.' — ' : '').'Сообщество 24Logist')
@section('description', $activeCategory?->description ?: 'Практические обсуждения перевозок, ЭДО и цифровой логистики.')

@section('content')
<div class="landing-shell community-layout">
    <section class="community-feed">
        <header class="community-hero">
            <div>
                <span class="section-kicker">Открытое сообщество</span>
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

        <nav class="community-sort" aria-label="Сортировка тем">
            <a @class(['is-active' => $sort === 'hot']) href="{{ request()->fullUrlWithQuery(['sort' => 'hot', 'period' => null, 'page' => null]) }}">Актуальное</a>
            <a @class(['is-active' => $sort === 'new']) href="{{ request()->fullUrlWithQuery(['sort' => 'new', 'period' => null, 'page' => null]) }}">Новое</a>
            <a @class(['is-active' => $sort === 'top']) href="{{ request()->fullUrlWithQuery(['sort' => 'top', 'page' => null]) }}">Лучшее</a>
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
                <div class="community-empty"><h2>Здесь пока тихо</h2><p>Станьте автором первой темы в этой рубрике.</p></div>
            @endforelse
        </div>
        <div class="community-pagination">{{ $posts->links() }}</div>
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
