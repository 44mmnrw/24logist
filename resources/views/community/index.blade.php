@extends('community.layout')

@section('title', ($activeCategory?->name ? $activeCategory->name.' — ' : '').'Сообщество 24Logist')
@section('description', $activeCategory?->description ?: 'Практические обсуждения перевозок, ЭДО и цифровой логистики.')

@section('content')
<div class="landing-shell community-layout">
    <section class="community-feed">
        <h1 class="community-feed__heading">{{ $activeCategory?->name ?: 'Обсуждения сообщества' }}</h1>

        @php($sortLabels = ['hot' => 'Актуальное', 'new' => 'Новое', 'top' => 'Лучшее', 'unanswered' => 'Ждут ответа'])
        <nav class="community-sort" aria-label="Сортировка тем" data-community-sort>
            <details class="community-sort__dropdown">
                <summary>{{ $sortLabels[$sort] }}<x-community.icon name="chevron-down" size="15" /></summary>
                <div class="community-sort__menu">
                    <span class="community-sort__menu-label">Сортировка по</span>
                    @foreach ($sortLabels as $value => $label)
                        <a @class(['is-active' => $sort === $value]) href="{{ request()->fullUrlWithQuery(['sort' => $value, 'period' => $value === 'top' ? $period : null, 'page' => null]) }}" @if ($sort === $value) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            </details>
            @if ($sort === 'top')
                @php($periodLabels = ['day' => 'Сутки', 'week' => 'Неделя', 'month' => 'Месяц', 'all' => 'Всё время'])
                <details class="community-sort__dropdown">
                    <summary aria-label="Период: {{ $periodLabels[$period] }}">{{ $periodLabels[$period] }}<x-community.icon name="chevron-down" size="15" /></summary>
                    <div class="community-sort__menu">
                        <span class="community-sort__menu-label">Период</span>
                        @foreach ($periodLabels as $value => $label)
                            <a @class(['is-active' => $period === $value]) href="{{ request()->fullUrlWithQuery(['period' => $value, 'page' => null]) }}" @if ($period === $value) aria-current="page" @endif>{{ $label }}</a>
                        @endforeach
                    </div>
                </details>
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
