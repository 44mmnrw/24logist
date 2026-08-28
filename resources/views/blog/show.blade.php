<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <x-analytics.google-analytics />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forBlogPost($post);
        $isPreview = $isPreview ?? false;
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :blog-post="$post" :robots="$isPreview ? 'noindex, nofollow, noarchive' : null" />
    @unless ($isPreview)
        <x-seo.structured-data :blog-post="$post" />
    @endunless
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
                        @if ($isPreview)
                            <div class="blog-preview-banner" role="status">
                                <strong>Закрытый предпросмотр</strong>
                                <span>Статья ещё не опубликована и доступна только по подписанной ссылке.</span>
                            </div>
                        @endif
                        <nav class="blog-breadcrumbs" aria-label="Хлебные крошки">
                            <ol>
                                <li><a href="{{ url('/') }}">Главная</a></li>
                                <li><a href="{{ route('blog.index') }}">Блог</a></li>
                                @if ($post->categoryUrl())
                                    <li><a href="{{ $post->categoryUrl() }}">{{ $post->displayCategory() }}</a></li>
                                @endif
                                <li aria-current="page"><span>{{ $post->title }}</span></li>
                            </ol>
                        </nav>
                        <div class="blog-card__meta">
                            @if ($post->displayCategory())
                                @if ($post->categoryUrl())
                                    <a class="blog-card__category" href="{{ $post->categoryUrl() }}">{{ $post->displayCategory() }}</a>
                                @else
                                    <span>{{ $post->displayCategory() }}</span>
                                @endif
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
                        @endif
                    </div>
                </header>

                <div @class([
                    'landing-shell',
                    'blog-post-layout',
                    'blog-post-layout--without-cover' => ! $post->articleImageUrl(),
                ])>
                    @if ($post->articleImageUrl())
                        <figure @class([
                            'blog-post-cover',
                            'blog-post-cover--branded' => $post->shouldShowArticleLogo(),
                            $post->logoPositionClass() => $post->shouldShowArticleLogo(),
                        ])>
                            <x-landing.responsive-image
                                :path="$post->articleImagePath()"
                                :alt="$post->cover_image_alt ?: $post->title"
                                width="1200"
                                height="675"
                                loading="eager"
                                fetchpriority="high"
                                sizes="(max-width: 1024px) calc(100vw - 32px), 920px"
                                picture-class="blog-post-cover__picture"
                            />
                            @if ($post->shouldShowArticleLogo())
                                <img
                                    class="blog-post-cover__logo"
                                    src="{{ asset('images/logo/logo_platform.png') }}"
                                    alt=""
                                    aria-hidden="true"
                                    width="360"
                                    height="88"
                                >
                            @endif
                        </figure>
                    @endif

                    <aside class="blog-post-aside">
                        <div class="blog-post-aside__box">
                            <span>Материал</span>
                            <strong>
                                @if ($post->categoryUrl())
                                    <a href="{{ $post->categoryUrl() }}">{{ $post->displayCategory() }}</a>
                                @else
                                    {{ $post->displayCategory() ?: 'Блог' }}
                                @endif
                            </strong>
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
                                    <a @class([
                                        'blog-card__media',
                                        'blog-card__media--branded' => $relatedPost->shouldShowCardLogo(),
                                        $relatedPost->logoPositionClass() => $relatedPost->shouldShowCardLogo(),
                                    ]) href="{{ $relatedPost->getUrl() }}" aria-label="{{ $relatedPost->title }}">
                                        @if ($relatedPost->cardImageUrl())
                                            <x-landing.responsive-image
                                                :path="$relatedPost->card_image_path ?: $relatedPost->cover_image_path"
                                                :alt="$relatedPost->cover_image_alt ?: $relatedPost->title"
                                                width="1200"
                                                height="675"
                                                loading="lazy"
                                                sizes="(max-width: 760px) calc(100vw - 32px), 33vw"
                                                :class="\Illuminate\Support\Arr::toCssClasses([
                                                    'blog-card__image',
                                                    'blog-card__image--prepared' => $relatedPost->hasPreparedCardImage(),
                                                ])"
                                                picture-class="blog-card__picture"
                                            />
                                        @else
                                            <div class="blog-card__image-placeholder">24L</div>
                                        @endif
                                    </a>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            @if ($relatedPost->displayCategory())
                                                @if ($relatedPost->categoryUrl())
                                                    <a class="blog-card__category" href="{{ $relatedPost->categoryUrl() }}">{{ $relatedPost->displayCategory() }}</a>
                                                @else
                                                    <span>{{ $relatedPost->displayCategory() }}</span>
                                                @endif
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
    <x-site.epd-presentation-popup />
    <x-site.telegram-popup />
    <x-site.cookie-consent />
    <x-analytics.yandex-metrika />
</body>
</html>
