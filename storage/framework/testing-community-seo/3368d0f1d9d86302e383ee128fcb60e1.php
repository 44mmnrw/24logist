<?php
    $section = $landing->section('footer');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($section): ?>
<footer class="landing-footer">
    <?php
        $extra = $section?->extra ?? [];
        $columns = $landing->blocks('footer', 'footer_column');
    ?>

    <div class="landing-shell landing-footer__top">
        <div class="landing-footer__brand">
            <a class="brand brand--footer" href="<?php echo e(\App\Support\LandingLinks::resolve('#hero')); ?>">
                <?php if (isset($component)) { $__componentOriginalc68929f56302ecb0e00ffa4b651ce40d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc68929f56302ecb0e00ffa4b651ce40d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.logo','data' => ['variant' => 'footer']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'footer']); ?>
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($section?->description): ?>
                <div class="landing-footer__description">
                    <?php echo $section->renderDescription(); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="landing-footer__col">
                <h3><?php echo e($column->title); ?></h3>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $column->children->where('block_type', 'footer_link'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(\App\Support\LandingLinks::resolve($link->link)); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($link->icon): ?>
                            <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => $link->icon]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link->icon)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b)): ?>
<?php $attributes = $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b; ?>
<?php unset($__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4685c1bec61dbcbf70087a04cfe5533b)): ?>
<?php $component = $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b; ?>
<?php unset($__componentOriginal4685c1bec61dbcbf70087a04cfe5533b); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php echo e($link->title); ?>

                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>

    <div class="landing-footer__bottom">
        <div class="landing-shell landing-footer__bottom-shell">
            <span><?php echo e($extra['copyright'] ?? ''); ?></span>
            <span>
                <a href="<?php echo e(route('referrals.partners.register')); ?>">Стать партнёром</a>
                <span aria-hidden="true"> · </span>
                <a href="<?php echo e(route('blog.index')); ?>">Блог</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\SiteSettingsService::class)->routeApiConfigured() && $landing->section('growth')): ?>
                    <span aria-hidden="true"> · </span><a href="<?php echo e(\App\Support\LandingLinks::resolve($landing->section('growth')->anchorLink() ?? '#growth')); ?>">Калькулятор маршрута</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\SiteSettingsService::class)->communityEnabled()): ?>
                    <span aria-hidden="true"> · </span><a href="<?php echo e(route('community.index')); ?>">Сообщество</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($extra['tagline'])): ?>
                    <span aria-hidden="true"> · </span><?php echo e($extra['tagline']); ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </span>
        </div>
    </div>
</footer>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/landing/footer.blade.php ENDPATH**/ ?>