@if ($photos->isNotEmpty())
    <div @class(['community-photo-gallery', 'community-photo-gallery--compact' => $compact ?? false, 'community-photo-gallery--feed' => $feed ?? false])>
        @foreach ($photos as $photo)
            <a href="{{ $photo->getUrl() }}" target="_blank" rel="noopener" aria-label="Открыть фото {{ $loop->iteration }}">
                <img src="{{ $photo->getUrl() }}" alt="Фото {{ $loop->iteration }}" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy" decoding="async">
            </a>
        @endforeach
    </div>
@endif
