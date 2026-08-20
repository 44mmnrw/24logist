<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <x-analytics.google-analytics />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forBlogTag($tag);
    @endphp
    <title>{{ $og['html_title'] }}</title>
    <x-seo.open-graph :blog-tag="$tag" />
    <x-seo.structured-data :blog-tag="$tag" />
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
                    <h1>{{ $tag->displayH1() }}</h1>
                    @if (filled($tag->description))
                        <p>{{ $tag->description }}</p>
                    @endif
                    <p class="blog-tag-count">
                        Найдено материалов: {{ $posts->total() }}
                    </p>
                </div>
            </section>

            <section class="blog-listing">
                <div class="landing-shell">
                    @if ($posts->count())
                        <div class="blog-grid">
                            @foreach ($posts as $post)
                                <article class="blog-card">
                                    <a class="blog-card__media" href="{{ $post->getUrl() }}" aria-label="{{ $post->title }}">
                                        @if ($post->coverImageUrl())
                                            <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->cover_image_alt ?: $post->title }}" loading="lazy">
                                        @else
                                            <div class="blog-card__image-placeholder">24L</div>
                                        @endif
                                    </a>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            @if ($post->displayCategory())
                                                <span>{{ $post->displayCategory() }}</span>
                                            @endif
                                            @if ($post->publishedDate())
                                                <time datetime="{{ $post->publishedDate()->toDateString() }}">{{ $post->publishedDate()->format('d.m.Y') }}</time>
                                            @endif
                                            @if ($post->reading_time_minutes)
                                                <span>{{ $post->reading_time_minutes }} мин</span>
                                            @endif
                                        </div>
                                        <h2><a href="{{ $post->getUrl() }}">{{ $post->title }}</a></h2>
                                        @if ($previewExcerpt = $post->previewExcerpt(160))
                                            <p>{{ $previewExcerpt }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($posts->hasPages())
                            <div class="blog-pagination">
                                {{ $posts->links() }}
                            </div>
                        @endif
                    @else
                        <div class="blog-empty">
                            <h2>Статей с таким тегом пока нет</h2>
                            <p>Посмотрите другие материалы в блоге или выберите другой тег.</p>
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
