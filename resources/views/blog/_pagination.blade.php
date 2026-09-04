@if ($paginator->hasPages())
    <nav class="blog-pagination__nav" role="navigation" aria-label="Навигация по страницам блога">
        <p class="blog-pagination__summary">
            Показано с <strong>{{ $paginator->firstItem() }}</strong>
            по <strong>{{ $paginator->lastItem() }}</strong>
            из <strong>{{ $paginator->total() }}</strong> материалов
        </p>

        <div class="blog-pagination__controls">
            @if ($paginator->onFirstPage())
                <span class="blog-pagination__link blog-pagination__link--wide is-disabled" aria-disabled="true">
                    <span aria-hidden="true">←</span>
                    Предыдущая
                </span>
            @else
                <a class="blog-pagination__link blog-pagination__link--wide" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <span aria-hidden="true">←</span>
                    Предыдущая
                </a>
            @endif

            <div class="blog-pagination__pages">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="blog-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <span class="blog-pagination__link is-current" aria-current="page" aria-label="Текущая страница, {{ $page }}">
                                    {{ $page }}
                                </span>
                            @else
                                <a class="blog-pagination__link" href="{{ $url }}" aria-label="Перейти на страницу {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a class="blog-pagination__link blog-pagination__link--wide" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    Следующая
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <span class="blog-pagination__link blog-pagination__link--wide is-disabled" aria-disabled="true">
                    Следующая
                    <span aria-hidden="true">→</span>
                </span>
            @endif
        </div>
    </nav>
@endif
