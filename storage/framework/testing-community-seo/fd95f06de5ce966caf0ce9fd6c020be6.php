<?php
    $canReact = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && (int) $target->community_user_id !== (int) auth('community')->id();
    $reactionCounts = $social['reactions'] ?? [];
    $selectedReactions = array_values((array) ($social['selected'] ?? []));
    $pickerId = 'community-reactions-'.$type.'-'.$target->id;
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canReact || array_sum($reactionCounts) > 0): ?>
    <details
        class="community-reactions"
        <?php if($canReact): ?>
            data-community-reactions
            data-type="<?php echo e($type); ?>"
            data-id="<?php echo e($target->id); ?>"
            data-endpoint="<?php echo e(route('community.react')); ?>"
        <?php endif; ?>
        aria-label="Реакции"
    >
        <summary
            class="community-reaction-trigger <?php if($selectedReactions !== []): ?> is-active <?php endif; ?>"
            data-reaction-toggle
            aria-label="Выбрать реакцию"
        >
            <span class="community-reaction-trigger__icons" data-reaction-trigger-icons>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $selectedReactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $selectedCode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <span class="community-reaction-chip">
                        <span class="community-reaction__emoji" aria-hidden="true"><?php echo e(\App\Services\Community\CommunitySocialService::REACTIONS[$selectedCode]['emoji']); ?></span>
                        <span class="community-reaction-chip__label"><?php echo e(\App\Services\Community\CommunitySocialService::REACTIONS[$selectedCode]['label']); ?></span>
                    </span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <span class="community-reaction-chip community-reaction-chip--empty">
                        <span class="community-reaction__emoji" aria-hidden="true">👍</span>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>
        </summary>

        <div class="community-reaction-picker" id="<?php echo e($pickerId); ?>" data-reaction-picker role="menu">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Services\Community\CommunitySocialService::REACTIONS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $reaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php $isSelected = in_array($code, $selectedReactions, true); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canReact): ?>
                <button
                    class="community-reaction-option <?php if($isSelected): ?> is-active <?php endif; ?>"
                    type="button"
                    data-code="<?php echo e($code); ?>"
                    data-emoji="<?php echo e($reaction['emoji']); ?>"
                    data-label="<?php echo e($reaction['label']); ?>"
                    role="menuitemcheckbox"
                    aria-checked="<?php echo e($isSelected ? 'true' : 'false'); ?>"
                    title="<?php echo e($reaction['label']); ?>"
                >
                    <span class="community-reaction__emoji" aria-hidden="true"><?php echo e($reaction['emoji']); ?></span>
                    <span class="community-reaction-option__label"><?php echo e($reaction['label']); ?></span>
                    <span class="community-reaction__count" data-reaction-count="<?php echo e($code); ?>"><?php echo e($reactionCounts[$code] ?? 0); ?></span>
                </button>
            <?php elseif(($reactionCounts[$code] ?? 0) > 0): ?>
                <span class="community-reaction-option is-readonly" role="menuitem">
                    <span class="community-reaction__emoji" aria-hidden="true"><?php echo e($reaction['emoji']); ?></span>
                    <span class="community-reaction-option__label"><?php echo e($reaction['label']); ?></span>
                    <span class="community-reaction__count"><?php echo e($reactionCounts[$code]); ?></span>
                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </details>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/shared/_reactions.blade.php ENDPATH**/ ?>