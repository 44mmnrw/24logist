<?php
    $awardCounts = $social['awards'] ?? [];
    $ownAward = $social['awarded'] ?? null;
    $awardTotal = array_sum($awardCounts);
    $awardedCodes = array_keys(array_filter($awardCounts));
    $feedAwardEmoji = count($awardedCodes) === 1
        ? (\App\Services\Community\CommunitySocialService::AWARDS[$awardedCodes[0]]['emoji'] ?? '🏅')
        : '🏅';
    $feed = $feed ?? false;
    $canAward = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && $target->community_user_id !== null
        && (int) $target->community_user_id !== (int) auth('community')->id();
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canAward || $awardTotal > 0): ?>
    <div class="community-awards" aria-label="Награды">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($feed): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($awardTotal > 0): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canAward && $ownAward !== null): ?>
                    <details class="community-award-menu">
                        <summary class="community-award-badge community-award-badge--removable" aria-label="Действия с наградой"><span aria-hidden="true"><?php echo e($feedAwardEmoji); ?></span><span><?php echo e($awardTotal); ?></span></summary>
                        <div class="community-award-menu__popover">
                            <form method="POST" action="<?php echo e(route('community.award.destroy')); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <input type="hidden" name="target_type" value="<?php echo e($type); ?>">
                                <input type="hidden" name="target_id" value="<?php echo e($target->id); ?>">
                                <button class="community-award-menu__delete" type="submit"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'trash','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','size' => '15']); ?>
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
<?php endif; ?>Удалить награду</button>
                            </form>
                        </div>
                    </details>
                <?php else: ?>
                    <span class="community-award-badge" title="Всего наград: <?php echo e($awardTotal); ?>"><span aria-hidden="true"><?php echo e($feedAwardEmoji); ?></span><span><?php echo e($awardTotal); ?></span></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php else: ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Services\Community\CommunitySocialService::AWARDS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $award): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($awardCounts[$code] ?? 0) > 0): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canAward && $ownAward === $code): ?>
                        <details class="community-award-menu">
                            <summary class="community-award-badge community-award-badge--removable" aria-label="Действия с наградой"><span aria-hidden="true"><?php echo e($award['emoji']); ?></span><span><?php echo e($awardCounts[$code]); ?></span></summary>
                            <div class="community-award-menu__popover">
                                <form method="POST" action="<?php echo e(route('community.award.destroy')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <input type="hidden" name="target_type" value="<?php echo e($type); ?>">
                                    <input type="hidden" name="target_id" value="<?php echo e($target->id); ?>">
                                    <button class="community-award-menu__delete" type="submit"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'trash','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','size' => '15']); ?>
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
<?php endif; ?>Удалить награду</button>
                                </form>
                            </div>
                        </details>
                    <?php else: ?>
                        <span class="community-award-badge" title="<?php echo e($award['label']); ?>"><span aria-hidden="true"><?php echo e($award['emoji']); ?></span><span><?php echo e($awardCounts[$code]); ?></span></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canAward && $ownAward === null): ?>
            <button class="community-award-open" type="button" data-award-open data-award-type="<?php echo e($type); ?>" data-award-id="<?php echo e($target->id); ?>" data-award-title="<?php echo e($type === 'post' ? $target->title : 'Автор: '.($target->author?->displayName() ?? 'участник')); ?>" aria-label="Наградить <?php echo e($type === 'post' ? 'автора темы' : 'автора комментария'); ?>"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'trophy','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trophy','size' => '16']); ?>
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
<?php endif; ?><span>Наградить</span></button>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/shared/_awards.blade.php ENDPATH**/ ?>