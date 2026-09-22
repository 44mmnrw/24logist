<?php
    $section = $landing->section('header');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($section): ?>
<header class="landing-header">
    <?php
        $navLinks = $landing->blocks('header', 'nav_link');
        $headerButtons = $landing->blocks('header', 'header_button');
        $heroSection = $landing->section('hero');
        $siteSettingsService = app(\App\Services\SiteSettingsService::class);
        $siteSettings = $siteSettingsService->get();
        $cabinetLoginEnabled = $siteSettingsService->cabinetLoginConfigured();
        $cabinetRegistrationEnabled = $siteSettingsService->cabinetRegistrationConfigured();
        $cabinetLoginButtonClass = match ($siteSettings->cabinet_login_button_style) {
            'primary' => 'btn btn--primary btn--sm cabinet-login-trigger',
            'link' => 'landing-header__login cabinet-login-trigger',
            default => 'btn btn--ghost btn--sm cabinet-login-trigger',
        };
        $cabinetRegistrationButtonClass = match ($siteSettings->cabinet_registration_button_style) {
            'ghost' => 'btn btn--ghost btn--sm cabinet-login-trigger',
            'link' => 'landing-header__login cabinet-login-trigger',
            default => 'btn btn--primary btn--sm cabinet-login-trigger',
        };
        $cabinetAuthHeaderEnabled = $cabinetLoginEnabled || $cabinetRegistrationEnabled;
    ?>

    <div class="landing-shell landing-header__shell">
        <a class="brand" href="<?php echo e(\App\Support\LandingLinks::resolve($heroSection?->anchorLink() ?? '#hero')); ?>">
            <?php if (isset($component)) { $__componentOriginalc68929f56302ecb0e00ffa4b651ce40d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc68929f56302ecb0e00ffa4b651ce40d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.logo','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc68929f56302ecb0e00ffa4b651ce40d)): ?>
<?php $attributes = $__attributesOriginalc68929f56302ecb0e00ffa4b651ce40d; ?>
<?php unset($__attributesOriginalc68929f56302ecb0e00ffa4b651ce40d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc68929f56302ecb0e00ffa4b651ce40d)): ?>
<?php $component = $__componentOriginalc68929f56302ecb0e00ffa4b651ce40d; ?>
<?php unset($__componentOriginalc68929f56302ecb0e00ffa4b651ce40d); ?>
<?php endif; ?>
        </a>

        <nav class="landing-nav">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <a href="<?php echo e(\App\Support\LandingLinks::resolve($link->link)); ?>"><?php echo e($link->title); ?></a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            
        </nav>

        <div class="landing-header__actions">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($cabinetAuthHeaderEnabled)): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $headerButtons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $button): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($button->button_style === 'primary'): ?>
                        <a class="btn btn--primary btn--sm" href="<?php echo e(\App\Support\LandingLinks::resolve($button->link)); ?>"><?php echo e($button->title); ?></a>
                    <?php else: ?>
                        <a class="landing-header__login" href="<?php echo e(\App\Support\LandingLinks::resolve($button->link)); ?>"><?php echo e($button->title); ?></a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cabinetRegistrationEnabled): ?>
                <button class="<?php echo e($cabinetRegistrationButtonClass); ?>" type="button" data-cabinet-registration-open>
                    <?php echo e($siteSettings->cabinet_registration_button_text ?: 'Создать личный кабинет'); ?>

                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cabinetLoginEnabled): ?>
                <button class="<?php echo e($cabinetLoginButtonClass); ?>" type="button" data-cabinet-login-open>
                    <?php echo e($siteSettings->cabinet_login_button_text ?: 'Войти в личный кабинет'); ?>

                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($navLinks->isNotEmpty() || $cabinetLoginEnabled || $cabinetRegistrationEnabled): ?>
                <details class="landing-mobile-menu">
                    <summary class="landing-mobile-menu__toggle" aria-label="Открыть меню">
                        <span class="landing-mobile-menu__icon" aria-hidden="true"></span>
                    </summary>

                    <nav class="landing-mobile-menu__panel" aria-label="Мобильная навигация">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $navLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <a href="<?php echo e(\App\Support\LandingLinks::resolve($link->link)); ?>"><?php echo e($link->title); ?></a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cabinetRegistrationEnabled): ?>
                            <button type="button" data-cabinet-registration-open><?php echo e($siteSettings->cabinet_registration_button_text ?: 'Создать личный кабинет'); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cabinetLoginEnabled): ?>
                            <button type="button" data-cabinet-login-open><?php echo e($siteSettings->cabinet_login_button_text ?: 'Войти в личный кабинет'); ?></button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                    </nav>
                </details>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/landing/header.blade.php ENDPATH**/ ?>