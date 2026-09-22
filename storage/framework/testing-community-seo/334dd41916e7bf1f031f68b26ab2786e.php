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
        $og = \App\Support\OpenGraph::forBlogPost($post);
        $isPreview = $isPreview ?? false;
    ?>
    <title><?php echo e($og['title']); ?></title>
    <?php if (isset($component)) { $__componentOriginal2473e7685210bc7add3c5100171e7867 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2473e7685210bc7add3c5100171e7867 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.seo.open-graph','data' => ['blogPost' => $post,'robots' => $isPreview ? 'noindex, nofollow, noarchive' : null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('seo.open-graph'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['blog-post' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post),'robots' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isPreview ? 'noindex, nofollow, noarchive' : null)]); ?>
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
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($isPreview)): ?>
        <?php if (isset($component)) { $__componentOriginald5870a7abe89714b8eaa34415f1eda89 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5870a7abe89714b8eaa34415f1eda89 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.seo.structured-data','data' => ['blogPost' => $post]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('seo.structured-data'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['blog-post' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5870a7abe89714b8eaa34415f1eda89)): ?>
<?php $attributes = $__attributesOriginald5870a7abe89714b8eaa34415f1eda89; ?>
<?php unset($__attributesOriginald5870a7abe89714b8eaa34415f1eda89); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5870a7abe89714b8eaa34415f1eda89)): ?>
<?php $component = $__componentOriginald5870a7abe89714b8eaa34415f1eda89; ?>
<?php unset($__componentOriginald5870a7abe89714b8eaa34415f1eda89); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    <div class="landing-page blog-page">
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

        <main class="blog-post-page">
            <article>
                <header class="blog-post-hero">
                    <div class="landing-shell blog-post-hero__shell">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isPreview): ?>
                            <div class="blog-preview-banner" role="status">
                                <strong>Закрытый предпросмотр</strong>
                                <span>Статья ещё не опубликована и доступна только по подписанной ссылке.</span>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <nav class="blog-breadcrumbs" aria-label="Хлебные крошки">
                            <ol>
                                <li><a href="<?php echo e(url('/')); ?>">Главная</a></li>
                                <li><a href="<?php echo e(route('blog.index')); ?>">Блог</a></li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->categoryUrl()): ?>
                                    <li><a href="<?php echo e($post->categoryUrl()); ?>"><?php echo e($post->displayCategory()); ?></a></li>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <li aria-current="page"><span><?php echo e($post->title); ?></span></li>
                            </ol>
                        </nav>
                        <div class="blog-card__meta">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->displayCategory()): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->categoryUrl()): ?>
                                    <a class="blog-card__category" href="<?php echo e($post->categoryUrl()); ?>"><?php echo e($post->displayCategory()); ?></a>
                                <?php else: ?>
                                    <span><?php echo e($post->displayCategory()); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->publishedDate()): ?>
                                <time datetime="<?php echo e($post->publishedDate()->toDateString()); ?>"><?php echo e($post->publishedDate()->format('d.m.Y')); ?></time>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->author_name): ?>
                                <span><?php echo e($post->author_name); ?></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->reading_time_minutes): ?>
                                <span><?php echo e($post->reading_time_minutes); ?> мин чтения</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <h1><?php echo e($post->title); ?></h1>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->subtitle): ?>
                            <p><?php echo e($post->subtitle); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </header>

                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'landing-shell',
                    'blog-post-layout',
                    'blog-post-layout--without-cover' => ! $post->articleImageUrl(),
                ]); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->articleImageUrl()): ?>
                        <figure class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'blog-post-cover',
                            'blog-post-cover--branded' => $post->shouldShowArticleLogo(),
                            $post->logoPositionClass() => $post->shouldShowArticleLogo(),
                        ]); ?>">
                            <?php if (isset($component)) { $__componentOriginal1c6c934482ffc91046668d3c6cb1952c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1c6c934482ffc91046668d3c6cb1952c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.responsive-image','data' => ['path' => $post->articleImagePath(),'alt' => $post->cover_image_alt ?: $post->title,'width' => '1200','height' => '675','loading' => 'eager','fetchpriority' => 'high','sizes' => '(max-width: 1024px) calc(100vw - 32px), 920px','pictureClass' => 'blog-post-cover__picture']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.responsive-image'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['path' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post->articleImagePath()),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post->cover_image_alt ?: $post->title),'width' => '1200','height' => '675','loading' => 'eager','fetchpriority' => 'high','sizes' => '(max-width: 1024px) calc(100vw - 32px), 920px','picture-class' => 'blog-post-cover__picture']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1c6c934482ffc91046668d3c6cb1952c)): ?>
<?php $attributes = $__attributesOriginal1c6c934482ffc91046668d3c6cb1952c; ?>
<?php unset($__attributesOriginal1c6c934482ffc91046668d3c6cb1952c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1c6c934482ffc91046668d3c6cb1952c)): ?>
<?php $component = $__componentOriginal1c6c934482ffc91046668d3c6cb1952c; ?>
<?php unset($__componentOriginal1c6c934482ffc91046668d3c6cb1952c); ?>
<?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->shouldShowArticleLogo()): ?>
                                <img
                                    class="blog-post-cover__logo"
                                    src="<?php echo e(asset('images/logo/logo_platform.png')); ?>"
                                    alt=""
                                    aria-hidden="true"
                                    width="360"
                                    height="88"
                                >
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </figure>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <aside class="blog-post-aside">
                        <div class="blog-post-aside__box">
                            <span>Материал</span>
                            <strong>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->categoryUrl()): ?>
                                    <a href="<?php echo e($post->categoryUrl()); ?>"><?php echo e($post->displayCategory()); ?></a>
                                <?php else: ?>
                                    <?php echo e($post->displayCategory() ?: 'Блог'); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </strong>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->publishedDate()): ?>
                                <p>Опубликовано <?php echo e($post->publishedDate()->format('d.m.Y')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </aside>

                    <div class="blog-post-main">
                        <div class="cms-page__body blog-post-body blog-post-body--article">
                            <?php echo $post->renderBody(); ?>

                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->tags): ?>
                            <div class="blog-tags" aria-label="Теги статьи">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $post->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($tag)): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tagLinks->has(trim((string) $tag))): ?>
                                            <a href="<?php echo e($tagLinks->get(trim((string) $tag))->getUrl()); ?>"><?php echo e($tag); ?></a>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </article>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedPosts->isNotEmpty()): ?>
                <section class="blog-related">
                    <div class="landing-shell">
                        <div class="section-head">
                            <h2>Похожие материалы</h2>
                        </div>
                        <div class="blog-grid blog-grid--related">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $relatedPosts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $relatedPost): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <article class="blog-card">
                                    <a class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                        'blog-card__media',
                                        'blog-card__media--branded' => $relatedPost->shouldShowCardLogo(),
                                        $relatedPost->logoPositionClass() => $relatedPost->shouldShowCardLogo(),
                                    ]); ?>" href="<?php echo e($relatedPost->getUrl()); ?>" aria-label="<?php echo e($relatedPost->title); ?>">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedPost->cardImageUrl()): ?>
                                            <?php if (isset($component)) { $__componentOriginal1c6c934482ffc91046668d3c6cb1952c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1c6c934482ffc91046668d3c6cb1952c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.responsive-image','data' => ['path' => $relatedPost->card_image_path ?: $relatedPost->cover_image_path,'alt' => $relatedPost->cover_image_alt ?: $relatedPost->title,'width' => '1200','height' => '675','loading' => 'lazy','sizes' => '(max-width: 760px) calc(100vw - 32px), 33vw','class' => \Illuminate\Support\Arr::toCssClasses([
                                                    'blog-card__image',
                                                    'blog-card__image--prepared' => $relatedPost->hasPreparedCardImage(),
                                                ]),'pictureClass' => 'blog-card__picture']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.responsive-image'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['path' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($relatedPost->card_image_path ?: $relatedPost->cover_image_path),'alt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($relatedPost->cover_image_alt ?: $relatedPost->title),'width' => '1200','height' => '675','loading' => 'lazy','sizes' => '(max-width: 760px) calc(100vw - 32px), 33vw','class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses([
                                                    'blog-card__image',
                                                    'blog-card__image--prepared' => $relatedPost->hasPreparedCardImage(),
                                                ])),'picture-class' => 'blog-card__picture']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1c6c934482ffc91046668d3c6cb1952c)): ?>
<?php $attributes = $__attributesOriginal1c6c934482ffc91046668d3c6cb1952c; ?>
<?php unset($__attributesOriginal1c6c934482ffc91046668d3c6cb1952c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1c6c934482ffc91046668d3c6cb1952c)): ?>
<?php $component = $__componentOriginal1c6c934482ffc91046668d3c6cb1952c; ?>
<?php unset($__componentOriginal1c6c934482ffc91046668d3c6cb1952c); ?>
<?php endif; ?>
                                        <?php else: ?>
                                            <div class="blog-card__image-placeholder">24L</div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </a>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedPost->displayCategory()): ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedPost->categoryUrl()): ?>
                                                    <a class="blog-card__category" href="<?php echo e($relatedPost->categoryUrl()); ?>"><?php echo e($relatedPost->displayCategory()); ?></a>
                                                <?php else: ?>
                                                    <span><?php echo e($relatedPost->displayCategory()); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($relatedPost->publishedDate()): ?>
                                                <time datetime="<?php echo e($relatedPost->publishedDate()->toDateString()); ?>"><?php echo e($relatedPost->publishedDate()->format('d.m.Y')); ?></time>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <h2><a href="<?php echo e($relatedPost->getUrl()); ?>"><?php echo e($relatedPost->title); ?></a></h2>
                                    </div>
                                </article>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
    <?php if (isset($component)) { $__componentOriginal0f14d32c3798c0213663c6158f6b7114 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f14d32c3798c0213663c6158f6b7114 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site.epd-presentation-popup','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site.epd-presentation-popup'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f14d32c3798c0213663c6158f6b7114)): ?>
<?php $attributes = $__attributesOriginal0f14d32c3798c0213663c6158f6b7114; ?>
<?php unset($__attributesOriginal0f14d32c3798c0213663c6158f6b7114); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f14d32c3798c0213663c6158f6b7114)): ?>
<?php $component = $__componentOriginal0f14d32c3798c0213663c6158f6b7114; ?>
<?php unset($__componentOriginal0f14d32c3798c0213663c6158f6b7114); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginala7d541c2e1ddf1cd803db3771ecb9df7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7d541c2e1ddf1cd803db3771ecb9df7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site.telegram-popup','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site.telegram-popup'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7d541c2e1ddf1cd803db3771ecb9df7)): ?>
<?php $attributes = $__attributesOriginala7d541c2e1ddf1cd803db3771ecb9df7; ?>
<?php unset($__attributesOriginala7d541c2e1ddf1cd803db3771ecb9df7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7d541c2e1ddf1cd803db3771ecb9df7)): ?>
<?php $component = $__componentOriginala7d541c2e1ddf1cd803db3771ecb9df7; ?>
<?php unset($__componentOriginala7d541c2e1ddf1cd803db3771ecb9df7); ?>
<?php endif; ?>
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
<?php /**PATH C:\laragon\www\24logistru\resources\views/blog/show.blade.php ENDPATH**/ ?>