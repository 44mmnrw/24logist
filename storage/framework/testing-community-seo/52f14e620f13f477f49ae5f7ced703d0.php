<?php ($existingPhotos = $existingPhotos ?? collect()); ?>
<?php ($compact = $compact ?? false); ?>
<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['community-photo-editor', 'community-photo-editor--compact' => $compact]); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($existingPhotos->isNotEmpty()): ?>
        <div class="community-photo-editor__existing" aria-label="Загруженные фото">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $existingPhotos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <label class="community-photo-editor__existing-item">
                    <img src="<?php echo e($photo->getUrl()); ?>" alt="Фото <?php echo e($loop->iteration); ?>" width="<?php echo e($photo->width); ?>" height="<?php echo e($photo->height); ?>" loading="lazy">
                    <span><input type="checkbox" name="remove_photos[]" value="<?php echo e($photo->id); ?>"> Удалить</span>
                </label>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($compact): ?>
        <input class="community-composer-sr-only" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple aria-label="Выбрать фото для комментария" data-community-photo-input data-max-photos="<?php echo e(\App\Services\Community\CommunityPhotoService::MAX_COUNT); ?>" data-max-bytes="<?php echo e(\App\Services\Community\CommunityPhotoService::MAX_FILE_KB * 1024); ?>">
    <?php else: ?>
        <label class="community-photo-editor__picker" data-community-dropzone>
            <span><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'photo-up','size' => '18']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'photo-up','size' => '18']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?> Перетащите фото сюда или выберите файлы</span>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-community-photo-input data-max-photos="<?php echo e(\App\Services\Community\CommunityPhotoService::MAX_COUNT); ?>" data-max-bytes="<?php echo e(\App\Services\Community\CommunityPhotoService::MAX_FILE_KB * 1024); ?>">
        </label>
        <small>До 3 фото, каждое до 2 МБ. JPEG, PNG или WebP.</small>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="community-photo-editor__preview" data-community-photo-preview aria-live="polite"></div>
</div>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/photos/_editor.blade.php ENDPATH**/ ?>