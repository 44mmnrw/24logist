<?php $__env->startSection('title', $user->displayName().' — Сообщество 24Logist'); ?>
<?php $__env->startSection('robots', 'noindex, follow'); ?>

<?php $__env->startSection('content'); ?>
<div class="landing-shell community-layout">
    <section class="community-feed">
        <header class="community-profile-header">
            <?php if (isset($component)) { $__componentOriginal701c92bfa02fab7fa95abe81787e2da7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.avatar','data' => ['user' => $user,'size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 'lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal701c92bfa02fab7fa95abe81787e2da7)): ?>
<?php $attributes = $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7; ?>
<?php unset($__attributesOriginal701c92bfa02fab7fa95abe81787e2da7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal701c92bfa02fab7fa95abe81787e2da7)): ?>
<?php $component = $__componentOriginal701c92bfa02fab7fa95abe81787e2da7; ?>
<?php unset($__componentOriginal701c92bfa02fab7fa95abe81787e2da7); ?>
<?php endif; ?>
            <div class="community-profile-details">
                <h1><?php echo e($communitySeo['h1']); ?></h1>
                <p class="community-profile-handle"><?php echo e('@'.$user->username); ?></p>
                <p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->transportRoleLabel()): ?><span><?php echo e($user->transportRoleLabel()); ?></span> · <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->show_karma): ?><?php echo e($user->karma); ?> рейтинга · <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    зарегистрирован <?php echo e($user->created_at->locale('ru')->translatedFormat('j F Y')); ?>

                </p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->bio): ?><p class="community-profile-bio"><?php echo e($user->bio); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </header>
        <h2>Темы пользователя</h2>
        <div class="community-posts"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $posts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><?php echo $__env->make('community.posts._card', ['post' => $post], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?><div class="community-empty">Пока нет опубликованных тем.</div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></div>
        <div class="community-pagination"><?php echo e($posts->links()); ?></div>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('community.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\24logistru\resources\views/community/profile.blade.php ENDPATH**/ ?>