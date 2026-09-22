<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (isset($component)) { $__componentOriginald6c96ad366dcdd275597a19bcaeda0e5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald6c96ad366dcdd275597a19bcaeda0e5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.csrf-meta','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('csrf-meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald6c96ad366dcdd275597a19bcaeda0e5)): ?>
<?php $attributes = $__attributesOriginald6c96ad366dcdd275597a19bcaeda0e5; ?>
<?php unset($__attributesOriginald6c96ad366dcdd275597a19bcaeda0e5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald6c96ad366dcdd275597a19bcaeda0e5)): ?>
<?php $component = $__componentOriginald6c96ad366dcdd275597a19bcaeda0e5; ?>
<?php unset($__componentOriginald6c96ad366dcdd275597a19bcaeda0e5); ?>
<?php endif; ?>
    <?php
        $og = \App\Support\OpenGraph::forNotFound();
    ?>
    <title><?php echo e($og['title']); ?></title>
    <?php if (isset($component)) { $__componentOriginal2473e7685210bc7add3c5100171e7867 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2473e7685210bc7add3c5100171e7867 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.seo.open-graph','data' => ['notFound' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('seo.open-graph'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['not-found' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2473e7685210bc7add3c5100171e7867)): ?>
<?php $attributes = $__attributesOriginal2473e7685210bc7add3c5100171e7867; ?>
<?php unset($__attributesOriginal2473e7685210bc7add3c5100171e7867); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2473e7685210bc7add3c5100171e7867)): ?>
<?php $component = $__componentOriginal2473e7685210bc7add3c5100171e7867; ?>
<?php unset($__componentOriginal2473e7685210bc7add3c5100171e7867); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal8b0069a18e0d194e46902168fbbae56b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b0069a18e0d194e46902168fbbae56b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site.favicon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site.favicon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b0069a18e0d194e46902168fbbae56b)): ?>
<?php $attributes = $__attributesOriginal8b0069a18e0d194e46902168fbbae56b; ?>
<?php unset($__attributesOriginal8b0069a18e0d194e46902168fbbae56b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b0069a18e0d194e46902168fbbae56b)): ?>
<?php $component = $__componentOriginal8b0069a18e0d194e46902168fbbae56b; ?>
<?php unset($__componentOriginal8b0069a18e0d194e46902168fbbae56b); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal479075eb67db74b586285f35a9f349be = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal479075eb67db74b586285f35a9f349be = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.fonts.preload','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('fonts.preload'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal479075eb67db74b586285f35a9f349be)): ?>
<?php $attributes = $__attributesOriginal479075eb67db74b586285f35a9f349be; ?>
<?php unset($__attributesOriginal479075eb67db74b586285f35a9f349be); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal479075eb67db74b586285f35a9f349be)): ?>
<?php $component = $__componentOriginal479075eb67db74b586285f35a9f349be; ?>
<?php unset($__componentOriginal479075eb67db74b586285f35a9f349be); ?>
<?php endif; ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <?php if (isset($component)) { $__componentOriginal72b1e122dde82c8775ef7b2a7e772ed4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72b1e122dde82c8775ef7b2a7e772ed4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.sprite','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.sprite'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72b1e122dde82c8775ef7b2a7e772ed4)): ?>
<?php $attributes = $__attributesOriginal72b1e122dde82c8775ef7b2a7e772ed4; ?>
<?php unset($__attributesOriginal72b1e122dde82c8775ef7b2a7e772ed4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72b1e122dde82c8775ef7b2a7e772ed4)): ?>
<?php $component = $__componentOriginal72b1e122dde82c8775ef7b2a7e772ed4; ?>
<?php unset($__componentOriginal72b1e122dde82c8775ef7b2a7e772ed4); ?>
<?php endif; ?>

    <div class="landing-page not-found-page">
        <?php if (isset($component)) { $__componentOriginalc23fa2ad28cfb67446e68e989474d981 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc23fa2ad28cfb67446e68e989474d981 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.header','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc23fa2ad28cfb67446e68e989474d981)): ?>
<?php $attributes = $__attributesOriginalc23fa2ad28cfb67446e68e989474d981; ?>
<?php unset($__attributesOriginalc23fa2ad28cfb67446e68e989474d981); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc23fa2ad28cfb67446e68e989474d981)): ?>
<?php $component = $__componentOriginalc23fa2ad28cfb67446e68e989474d981; ?>
<?php unset($__componentOriginalc23fa2ad28cfb67446e68e989474d981); ?>
<?php endif; ?>

        <main class="not-found-page__main">
            <div class="landing-shell not-found-page__shell">
                <div class="not-found-page__card">
                    <div class="not-found-page__hero" aria-hidden="true">
                        <p class="not-found-page__watermark">404</p>
                        <div class="not-found-page__hero-content">
                            <div class="not-found-page__icon-wrap">
                                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'truck','class' => 'not-found-page__truck-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'truck','class' => 'not-found-page__truck-icon']); ?>
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
                            </div>
                            <div class="not-found-page__route-badge">
                                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'rotes','class' => 'not-found-page__route-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'rotes','class' => 'not-found-page__route-icon']); ?>
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
                                <span>Маршрут не найден</span>
                            </div>
                        </div>
                    </div>

                    <h1 class="not-found-page__title">Страница заблудилась в пути</h1>

                    <p class="not-found-page__desc">
                        Запрошенный адрес не существует или был перемещён.
                        Вернитесь на главную — там все маршруты на месте.
                    </p>

                    <div class="not-found-page__error-pill">
                        <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'info-circle','class' => 'not-found-page__error-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'info-circle','class' => 'not-found-page__error-icon']); ?>
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
                        <span>Код ошибки: 404 Page Not Found</span>
                    </div>

                    <div class="not-found-page__actions">
                        <a href="<?php echo e(url('/')); ?>" class="btn btn--primary">
                            <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'home']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'home']); ?>
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
                            На главную
                        </a>
                        <a href="<?php echo e(url('/pages/contacts')); ?>" class="btn btn--ghost not-found-page__btn-secondary">
                            <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'phone']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'phone']); ?>
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
                            Связаться с нами
                        </a>
                    </div>

                    <nav class="not-found-page__quick-links" aria-label="Быстрая навигация">
                        <span class="not-found-page__quick-label">Попробуйте перейти в:</span>
                        <div class="not-found-page__quick-list">
                            <a href="<?php echo e(\App\Support\LandingLinks::resolve('#features')); ?>">
                                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'arrow-right','class' => 'not-found-page__link-arrow']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','class' => 'not-found-page__link-arrow']); ?>
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
                                Возможности
                            </a>
                            <a href="<?php echo e(\App\Support\LandingLinks::resolve('#pricing')); ?>">
                                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'arrow-right','class' => 'not-found-page__link-arrow']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','class' => 'not-found-page__link-arrow']); ?>
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
                                Тарифы
                            </a>
                            <a href="<?php echo e(url('/pages/contacts')); ?>">
                                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'arrow-right','class' => 'not-found-page__link-arrow']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','class' => 'not-found-page__link-arrow']); ?>
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
                                Контакты
                            </a>
                        </div>
                    </nav>

                    <div class="not-found-page__route-bar" aria-hidden="true">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php for($i = 0; $i < 7; $i++): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <span></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php if (isset($component)) { $__componentOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.footer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb)): ?>
<?php $attributes = $__attributesOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb; ?>
<?php unset($__attributesOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb)): ?>
<?php $component = $__componentOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb; ?>
<?php unset($__componentOriginalf4bb5a8e7d7746ba09a8b9ffce22b5fb); ?>
<?php endif; ?>
    </div>
    <?php if (isset($component)) { $__componentOriginalf1cbc7443f6d857dfe6d9cf1b061fca6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf1cbc7443f6d857dfe6d9cf1b061fca6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site.cookie-consent','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site.cookie-consent'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf1cbc7443f6d857dfe6d9cf1b061fca6)): ?>
<?php $attributes = $__attributesOriginalf1cbc7443f6d857dfe6d9cf1b061fca6; ?>
<?php unset($__attributesOriginalf1cbc7443f6d857dfe6d9cf1b061fca6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf1cbc7443f6d857dfe6d9cf1b061fca6)): ?>
<?php $component = $__componentOriginalf1cbc7443f6d857dfe6d9cf1b061fca6; ?>
<?php unset($__componentOriginalf1cbc7443f6d857dfe6d9cf1b061fca6); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.analytics.yandex-metrika','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('analytics.yandex-metrika'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3)): ?>
<?php $attributes = $__attributesOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3; ?>
<?php unset($__attributesOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3)): ?>
<?php $component = $__componentOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3; ?>
<?php unset($__componentOriginalc8bbcf8ddcb47da2b2daa5a11c43a1f3); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal744675f602907402b67dbf131fa31564 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal744675f602907402b67dbf131fa31564 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.analytics.google-analytics','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('analytics.google-analytics'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal744675f602907402b67dbf131fa31564)): ?>
<?php $attributes = $__attributesOriginal744675f602907402b67dbf131fa31564; ?>
<?php unset($__attributesOriginal744675f602907402b67dbf131fa31564); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal744675f602907402b67dbf131fa31564)): ?>
<?php $component = $__componentOriginal744675f602907402b67dbf131fa31564; ?>
<?php unset($__componentOriginal744675f602907402b67dbf131fa31564); ?>
<?php endif; ?>
</body>
</html>
<?php /**PATH C:\laragon\www\24logistru\resources\views/errors/404.blade.php ENDPATH**/ ?>