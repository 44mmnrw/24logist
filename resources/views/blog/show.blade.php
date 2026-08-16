<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forBlogPost($post);
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :blog-post="$post" />
    <x-seo.structured-data :blog-post="$post" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page blog-page">
        <x-landing.header />

        <main class="blog-post-page">
            <article>
                <header class="blog-post-hero">
                    <div class="landing-shell blog-post-hero__shell">
                        <nav class="blog-breadcrumbs" aria-label="Хлебные крошки">
                            <ol>
                                <li><a href="{{ url('/') }}">Главная</a></li>
                                <li><a href="{{ route('blog.index') }}">Блог</a></li>
                                <li aria-current="page"><span>{{ $post->title }}</span></li>
                            </ol>
                        </nav>
                        <div class="blog-card__meta">
                            @if ($post->displayCategory())
                                <span>{{ $post->displayCategory() }}</span>
                            @endif
                            @if ($post->publishedDate())
                                <time datetime="{{ $post->publishedDate()->toDateString() }}">{{ $post->publishedDate()->format('d.m.Y') }}</time>
                            @endif
                            @if ($post->author_name)
                                <span>{{ $post->author_name }}</span>
                            @endif
                            @if ($post->reading_time_minutes)
                                <span>{{ $post->reading_time_minutes }} мин чтения</span>
                            @endif
                        </div>
                        <h1>{{ $post->title }}</h1>
                        @if ($post->subtitle)
                            <p>{{ $post->subtitle }}</p>
                        @elseif ($post->displayExcerpt())
                            <p>{{ $post->displayExcerpt() }}</p>
                        @endif
                    </div>
                </header>

                <div @class([
                    'landing-shell',
                    'blog-post-layout',
                    'blog-post-layout--without-cover' => ! $post->coverImageUrl(),
                ])>
                    @if ($post->coverImageUrl())
                        <figure class="blog-post-cover">
                            <img src="{{ $post->coverImageUrl() }}" alt="{{ $post->cover_image_alt ?: $post->title }}">
                        </figure>
                    @endif

                    <aside class="blog-post-aside">
                        <div class="blog-post-aside__box">
                            <span>Материал</span>
                            <strong>{{ $post->displayCategory() ?: 'Блог' }}</strong>
                            @if ($post->publishedDate())
                                <p>Опубликовано {{ $post->publishedDate()->format('d.m.Y') }}</p>
                            @endif
                        </div>
                    </aside>

                    <div class="blog-post-main">
                        <div class="cms-page__body blog-post-body blog-post-body--article">
                            {!! $post->renderBody() !!}
                        </div>

                        @if ($post->tags)
                            <div class="blog-tags" aria-label="Теги статьи">
                                @foreach ($post->tags as $tag)
                                    @if (filled($tag))
                                        @if ($tagLinks->has(trim((string) $tag)))
                                            <a href="{{ $tagLinks->get(trim((string) $tag))->getUrl() }}">{{ $tag }}</a>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            @if ($relatedPosts->isNotEmpty())
                <section class="blog-related">
                    <div class="landing-shell">
                        <div class="section-head">
                            <h2>Похожие материалы</h2>
                        </div>
                        <div class="blog-grid blog-grid--related">
                            @foreach ($relatedPosts as $relatedPost)
                                <article class="blog-card">
                                    <a class="blog-card__media" href="{{ $relatedPost->getUrl() }}" aria-label="{{ $relatedPost->title }}">
                                        @if ($relatedPost->coverImageUrl())
                                            <img src="{{ $relatedPost->coverImageUrl() }}" alt="{{ $relatedPost->cover_image_alt ?: $relatedPost->title }}" loading="lazy">
                                        @else
                                            <div class="blog-card__image-placeholder">24L</div>
                                        @endif
                                    </a>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            @if ($relatedPost->displayCategory())
                                                <span>{{ $relatedPost->displayCategory() }}</span>
                                            @endif
                                            @if ($relatedPost->publishedDate())
                                                <time datetime="{{ $relatedPost->publishedDate()->toDateString() }}">{{ $relatedPost->publishedDate()->format('d.m.Y') }}</time>
                                            @endif
                                        </div>
                                        <h2><a href="{{ $relatedPost->getUrl() }}">{{ $relatedPost->title }}</a></h2>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        </main>

        <x-landing.footer />
    </div>
    <x-site.telegram-popup />
    <x-analytics.yandex-metrika />
</body>
</html>
