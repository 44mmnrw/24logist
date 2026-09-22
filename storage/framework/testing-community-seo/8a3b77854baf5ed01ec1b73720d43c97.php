<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($categories->isNotEmpty()): ?>
    <nav class="blog-categories" aria-label="Рубрики блога">
        <div class="landing-shell">
            <ul class="blog-categories__list">
                <li>
                    <a class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'blog-category-link',
                        'blog-category-link--active' => ! isset($activeCategory),
                    ]); ?>"
                        href="<?php echo e(route('blog.index')); ?>"
                        <?php if(! isset($activeCategory)): ?> aria-current="page" <?php endif; ?>
                    >Все статьи</a>
                </li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navigationCategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php ($isActive = isset($activeCategory) && $activeCategory->is($navigationCategory)); ?>
                    <li>
                        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'blog-category-link',
                            'blog-category-link--active' => $isActive,
                        ]); ?>"
                            href="<?php echo e($navigationCategory->getUrl()); ?>"
                            <?php if($isActive): ?> aria-current="page" <?php endif; ?>
                        ><?php echo e($navigationCategory->name); ?></a>
                    </li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    </nav>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/blog/_categories.blade.php ENDPATH**/ ?>