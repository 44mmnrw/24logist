<?php $__env->startSection('title', (request()->routeIs('community.register') ? 'Регистрация' : 'Вход').' — Сообщество 24Logist'); ?>
<?php $__env->startSection('robots', 'noindex, nofollow'); ?>

<?php $__env->startSection('content'); ?>
<div class="landing-shell community-auth-shell">
    <div class="community-auth-card">
        <span class="section-kicker">Без пароля</span>
        <h1><?php echo e($communitySeo['h1']); ?></h1>
        <p><?php echo e(request()->routeIs('community.register') ? 'Выберите способ регистрации. После подтверждения входа вы создадите публичный профиль и зададите псевдоним.' : 'Выберите удобный способ входа. Публично будет виден только псевдоним, который вы зададите после входа.'); ?></p>
        <div class="community-auth-buttons">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\SiteSettingsService::class)->communityTelegramEnabled()): ?>
                <a class="community-provider community-provider--telegram" href="<?php echo e(route('community.auth.telegram.redirect')); ?>">Продолжить через Telegram</a>
            <?php else: ?>
                <span class="community-provider is-disabled">Telegram — скоро</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\SiteSettingsService::class)->communityVkEnabled()): ?>
                <a class="community-provider community-provider--vk" href="<?php echo e(route('community.auth.vk.redirect')); ?>">Продолжить через VK ID</a>
            <?php else: ?>
                <span class="community-provider is-disabled">VK ID — скоро</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\SiteSettingsService::class)->communityMaxEnabled()): ?>
                <a class="community-provider community-provider--max" href="<?php echo e(route('community.auth.max.start')); ?>">Продолжить через MAX</a>
            <?php else: ?>
                <span class="community-provider is-disabled">MAX — скоро</span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <small>Продолжая, вы соглашаетесь с <a href="<?php echo e(route('community.rules')); ?>">правилами сообщества</a> и <a href="<?php echo e(route('community.privacy')); ?>">политикой конфиденциальности</a>.</small>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('community.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\24logistru\resources\views/community/auth/login.blade.php ENDPATH**/ ?>