<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth('community')->check() && auth('community')->user()->isOnboarded()): ?>
    <dialog class="community-award-dialog" data-award-dialog aria-labelledby="community-award-title">
        <div class="community-award-dialog__top">
            <h2 id="community-award-title">Наградить участника</h2>
            <button class="community-award-dialog__close" type="button" data-award-close aria-label="Закрыть"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
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
        <p class="community-award-dialog__target" data-award-target></p>
        <form method="POST" action="<?php echo e(route('community.award')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="target_type" data-award-type>
            <input type="hidden" name="target_id" data-award-id>
            <fieldset class="community-award-grid">
                <legend>Выберите награду</legend>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Services\Community\CommunitySocialService::AWARDS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $award): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <label class="community-award-choice">
                        <input type="radio" name="code" value="<?php echo e($code); ?>" required <?php if($loop->first): echo 'checked'; endif; ?>>
                        <span class="community-award-choice__body"><span class="community-award-choice__emoji" aria-hidden="true"><?php echo e($award['emoji']); ?></span><span class="community-award-choice__name"><?php echo e($award['label']); ?></span><span class="community-award-choice__free">Бесплатно</span></span>
                    </label>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </fieldset>
            <label class="community-award-dialog__anonymous"><input type="checkbox" name="is_anonymous" value="1"> Отправить анонимно</label>
            <label class="community-award-dialog__message" for="community-award-message">Сообщение к награде <span>необязательно</span></label>
            <textarea id="community-award-message" name="message" maxlength="100" rows="2" placeholder="Поблагодарите автора несколькими словами" data-award-message></textarea>
            <div class="community-award-dialog__bottom"><small><span data-award-length>0</span>/100</small><button class="btn btn--primary" type="submit">Вручить награду</button></div>
        </form>
    </dialog>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/shared/_award_dialog.blade.php ENDPATH**/ ?>