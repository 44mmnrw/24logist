<form method="POST" action="<?php echo e(route('community.comments.store', $post)); ?>" enctype="multipart/form-data" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['community-comment-form', 'community-comment-form--root' => ! $parent]); ?>" data-community-composer>
    <?php echo csrf_field(); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($parent): ?><input type="hidden" name="parent_id" value="<?php echo e($parent->id); ?>"><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="community-comment-composer" data-community-dropzone data-rich-editor>
        <?php echo $__env->make('community.comments._format_toolbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <label class="community-composer-sr-only" for="community-comment-body-<?php echo e($parent?->id ?? 'root'); ?>"><?php echo e($parent ? 'Ваш ответ' : 'Вступить в беседу'); ?></label>
        <textarea id="community-comment-body-<?php echo e($parent?->id ?? 'root'); ?>" name="body_markdown" maxlength="5000" rows="1" placeholder="<?php echo e($parent ? 'Ваш ответ…' : 'Вступить в беседу'); ?>" data-composer-textarea></textarea>
        <div class="community-rich-editor__surface" data-rich-editor-surface hidden></div>
        <?php echo $__env->make('community.photos._editor', ['compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="community-comment-composer__toolbar">
            <div class="community-comment-composer__tools">
                <button class="community-comment-composer__tool" type="button" data-composer-photo-trigger title="Добавить фото — до 3 файлов JPEG, PNG или WebP" aria-label="Добавить фото"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
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
<?php endif; ?></button>
                <span class="community-comment-composer__file-count" data-composer-file-count hidden></span>
            </div>
            <div class="community-comment-composer__actions">
                <button class="community-comment-composer__cancel" type="reset">Отменить</button>
                <button class="btn btn--primary btn--sm community-comment-composer__submit" type="submit"><?php echo e($parent ? 'Ответить' : 'Комментарий'); ?></button>
            </div>
        </div>
    </div>
</form>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/comments/_form.blade.php ENDPATH**/ ?>