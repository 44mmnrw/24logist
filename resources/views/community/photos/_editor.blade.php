@php($existingPhotos = $existingPhotos ?? collect())
<div class="community-photo-editor">
    @if ($existingPhotos->isNotEmpty())
        <div class="community-photo-editor__existing" aria-label="Загруженные фото">
            @foreach ($existingPhotos as $photo)
                <label class="community-photo-editor__existing-item">
                    <img src="{{ $photo->getUrl() }}" alt="Фото {{ $loop->iteration }}" width="{{ $photo->width }}" height="{{ $photo->height }}" loading="lazy">
                    <span><input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}"> Удалить</span>
                </label>
            @endforeach
        </div>
    @endif
    <label class="community-photo-editor__picker" data-community-dropzone>
        <span><x-community.icon name="photo-up" size="18" /> Перетащите фото сюда или выберите файлы</span>
        <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-community-photo-input data-max-photos="{{ \App\Services\Community\CommunityPhotoService::MAX_COUNT }}" data-max-bytes="{{ \App\Services\Community\CommunityPhotoService::MAX_FILE_KB * 1024 }}">
    </label>
    <small>До 3 фото, каждое до 2 МБ. JPEG, PNG или WebP.</small>
    <div class="community-photo-editor__preview" data-community-photo-preview aria-live="polite"></div>
</div>
