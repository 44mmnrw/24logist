<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forBlogIndex();
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph blog-index />
    <x-seo.structured-data blog-index />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page blog-page">
        <x-landing.header />

        @php
            $siteSettings = app(\App\Services\SiteSettingsService::class)->get();
            $blogKicker = filled($siteSettings->blog_kicker) ? (string) $siteSettings->blog_kicker : 'Блог 24Logist';
            $blogTitle = filled($siteSettings->blog_title) ? (string) $siteSettings->blog_title : 'Практика цифровой логистики';
            $blogDescription = filled($siteSettings->blog_description)
                ? (string) $siteSettings->blog_description
                : 'Разбираем перевозки, автоматизацию, документооборот, контроль рейсов и управленческие процессы без лишней теории.';
        @endphp

        <main>
            <section class="blog-hero">
                <div class="landing-shell blog-hero__shell">
                    @if ($blogKicker !== '')
                        <div class="section-kicker">{{ $blogKicker }}</div>
                    @endif
                    <h1>{{ $blogTitle }}</h1>
                    @if ($blogDescription !== '')
                        <p>{{ $blogDescription }}</p>
                    @endif
                </div>
            </section>

            <section class="blog-listing">
                <div class="landing-shell">
                    @if ($featuredPost)
                        <article class="blog-featured">
                            <a class="blog-featured__media" href="{{ $featuredPost->getUrl() }}" aria-label="{{ $featuredPost->title }}">
                                @if ($featuredPost->coverImageUrl())
                                    <img src="{{ $featuredPost->coverImageUrl() }}" alt="{{ $featuredPost->cover_image_alt ?: $featuredPost->title }}" loading="eager">
                                @else
                                    <div class="blog-card__image-placeholder">24L</div>
                                @endif
                            </a>
                            <div class="blog-featured__body">
                                <div class="blog-card__meta">
                                    @if ($featuredPost->displayCategory())
                                        <span>{{ $featuredPost->displayCategory() }}</span>
                                    @endif
                                    @if ($featuredPost->publishedDate())
                                        <time datetime="{{ $featuredPost->publishedDate()->toDateString() }}">{{ $featuredPost->publishedDate()->format('d.m.Y') }}</time>
                                    @endif
                                    @if ($featuredPost->reading_time_minutes)
                                        <span>{{ $featuredPost->reading_time_minutes }} мин</span>
                                    @endif
                                </div>
                                <h2><a href="{{ $featuredPost->getUrl() }}">{{ $featuredPost->title }}</a></h2>
                                @if ($featuredPost->displayExcerpt())
                                    <p>{{ $featuredPost->displayExcerpt() }}</p>
                                @endif
                                <a class="btn btn--primary" href="{{ $featuredPost->getUrl() }}">
                                    Читать статью
                                    <x-landing.icon name="arrow-right" />
                                </a>
                            </div>
                        </article>
                    @endif

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
                                        @if ($post->displayExcerpt())
                                            <p>{{ $post->displayExcerpt() }}</p>
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
                            <h2>Статьи пока не опубликованы</h2>
                            <p>Когда в админке появятся опубликованные материалы, они автоматически отобразятся здесь.</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>

        <x-landing.footer />
    </div>
    <x-analytics.yandex-metrika />
</body>
</html>
