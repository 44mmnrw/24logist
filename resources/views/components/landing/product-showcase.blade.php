@php
    $section = $landing->section('product_showcase');
    $extra = $section?->extra ?? [];
    $banners = collect($extra['banners'] ?? [])
        ->filter(fn (mixed $banner): bool => is_array($banner) && filled($banner['title'] ?? null))
        ->values();
@endphp

@if ($section && $banners->isNotEmpty())
<section class="product-showcase" data-product-carousel @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif aria-label="Баннеры возможностей">
    <div class="product-showcase__stage">
        <div class="product-showcase__track" data-product-track>
            @foreach ($banners as $banner)
                @php($image = \App\Support\LandingMedia::normalizePath($banner['image'] ?? null))
                <article class="product-showcase__slide {{ $image ? 'product-showcase__slide--with-image' : '' }}" data-product-slide @if(!$loop->first) aria-hidden="true" inert @endif>
                    @if ($image)
                        <x-landing.responsive-image
                            :path="$image"
                            :alt="filled($banner['alt'] ?? null) ? $banner['alt'] : $banner['title']"
                            width="1920"
                            height="1080"
                            :loading="$loop->index < 2 ? 'eager' : 'lazy'"
                            sizes="100vw"
                            class="product-showcase__image"
                        />
                    @endif
                    <div class="landing-shell product-showcase__inner">
                        <div class="product-showcase__copy">
                            <h2>{{ $banner['title'] }}</h2>
                            @if (filled($banner['description'] ?? null))
                                <p>{{ $banner['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @if ($banners->count() > 1)
        <div class="product-showcase__dots" aria-label="Выбор баннера">
            @foreach ($banners as $banner)
                <button type="button" class="product-showcase__dot {{ $loop->first ? 'is-active' : '' }}" data-product-select="{{ $loop->index }}" aria-label="Показать баннер {{ $loop->iteration }}: {{ $banner['title'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    @endif
</section>
@endif
