@if ($categories->isNotEmpty())
    <nav class="blog-categories" aria-label="Рубрики блога">
        <div class="landing-shell">
            <ul class="blog-categories__list">
                <li>
                    <a @class([
                        'blog-category-link',
                        'blog-category-link--active' => ! isset($activeCategory),
                    ])
                        href="{{ route('blog.index') }}"
                        @if (! isset($activeCategory)) aria-current="page" @endif
                    >Все статьи</a>
                </li>
                @foreach ($categories as $navigationCategory)
                    @php($isActive = isset($activeCategory) && $activeCategory->is($navigationCategory))
                    <li>
                        <a @class([
                            'blog-category-link',
                            'blog-category-link--active' => $isActive,
                        ])
                            href="{{ $navigationCategory->getUrl() }}"
                            @if ($isActive) aria-current="page" @endif
                        >{{ $navigationCategory->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
