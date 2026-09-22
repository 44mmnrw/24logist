<dialog class="community-report-dialog" data-report-dialog aria-labelledby="community-report-title">
    <div class="community-report-dialog__header">
        <div>
            <span class="section-kicker">Обращение модераторам</span>
            <h2 id="community-report-title">Пожаловаться</h2>
        </div>
        <button class="community-report-dialog__close" type="button" data-report-close aria-label="Закрыть"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'x','size' => '20']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '20']); ?>
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
    </div>

    <form method="POST" action="<?php echo e(route('community.report')); ?>" class="community-report-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="target_type" value="" data-report-target-type>
        <input type="hidden" name="target_id" value="" data-report-target-id>

        <label>
            <span>Что случилось?</span>
            <select name="reason" required>
                <option value="">Выберите причину</option>
                <option value="spam">Спам или реклама</option>
                <option value="abuse">Оскорбления</option>
                <option value="illegal">Незаконный материал</option>
                <option value="personal_data">Персональные данные</option>
                <option value="other">Другое</option>
            </select>
        </label>

        <label>
            <span>Комментарий <small>(необязательно)</small></span>
            <textarea name="details" maxlength="1000" rows="4" placeholder="Коротко опишите проблему"></textarea>
        </label>

        <div class="community-report-dialog__actions">
            <button class="btn btn--sm" type="button" data-report-close>Отмена</button>
            <button class="btn btn--primary btn--sm" type="submit">Отправить жалобу</button>
        </div>
    </form>
</dialog>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/shared/_report_dialog.blade.php ENDPATH**/ ?>