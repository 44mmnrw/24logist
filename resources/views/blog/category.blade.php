<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <x-analytics.google-analytics />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forBlogCategory($category);
    @endphp
    <title>{{ $og['html_title'] }}</title>
    <x-seo.open-graph :blog-category="$category" />
    <x-seo.structured-data :blog-category="$category" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page blog-page">
        <x-landing.header />

        <main>
            <section class="blog-hero blog-tag-hero">
                <div class="landing-shell blog-hero__shell">
                    <a class="blog-tag-hero__back" href="{{ route('blog.index') }}">← Все статьи</a>
                    <h1>{{ $category->displayH1() }}</h1>
                    @if (filled($category->description))
                        <p>{{ $category->description }}</p>
                    @endif
                    <p class="blog-tag-count">Найдено материалов: {{ $posts->total() }}</p>
                </div>
            </section>

            <section class="blog-listing">
                <div class="landing-shell">
                    @if ($posts->count())
                        <div class="blog-grid">
                            @foreach ($posts as $post)
                                <article class="blog-card">
                                    <a @class([
                                        'blog-card__media',
                                        'blog-card__media--branded' => $post->shouldShowCardLogo(),
                                        $post->logoPositionClass() => $post->shouldShowCardLogo(),
                                    ]) href="{{ $post->getUrl() }}" aria-label="{{ $post->title }}">
                                        @if ($post->cardImageUrl())
                                            <img @class([
                                                'blog-card__image',
                                                'blog-card__image--prepared' => $post->hasPreparedCardImage(),
                                            ]) src="{{ $post->cardImageUrl() }}" alt="{{ $post->cover_image_alt ?: $post->title }}" loading="lazy">
                                        @else
                                            <div class="blog-card__image-placeholder">24L</div>
                                        @endif
                                    </a>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            <a class="blog-card__category" href="{{ $category->getUrl() }}">{{ $category->name }}</a>
                                            @if ($post->publishedDate())
                                                <time datetime="{{ $post->publishedDate()->toDateString() }}">{{ $post->publishedDate()->format('d.m.Y') }}</time>
                                            @endif
                                            @if ($post->reading_time_minutes)
                                                <span>{{ $post->reading_time_minutes }} мин</span>
                                            @endif
                                        </div>
                                        <h2><a href="{{ $post->getUrl() }}">{{ $post->title }}</a></h2>
                                        @if ($previewExcerpt = $post->previewExcerpt(120))
                                            <p>{{ $previewExcerpt }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($posts->hasPages())
                            <div class="blog-pagination">{{ $posts->links() }}</div>
                        @endif
                    @else
                        <div class="blog-empty">
                            <h2>В этой рубрике пока нет статей</h2>
                            <p>Посмотрите другие материалы блога или вернитесь позже.</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <x-landing.footer />
    </div>
    <x-site.telegram-popup />
    <x-site.cookie-consent />
    <x-analytics.yandex-metrika />
</body>
</html>
