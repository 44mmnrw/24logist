<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'path',
    'alt' => '',
    'width',
    'height',
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'sizes' => null,
    'class' => null,
    'pictureClass' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'path',
    'alt' => '',
    'width',
    'height',
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'sizes' => null,
    'class' => null,
    'pictureClass' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $image = \App\Support\ImageVariants::data($path);
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image['url']): ?>
    <picture <?php if($pictureClass): ?> class="<?php echo e($pictureClass); ?>" <?php endif; ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image['avif_srcset']): ?>
            <source
                type="image/avif"
                srcset="<?php echo e($image['avif_srcset']); ?>"
                <?php if($sizes): ?> sizes="<?php echo e($sizes); ?>" <?php endif; ?>
            >
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($image['webp_srcset']): ?>
            <source
                type="image/webp"
                srcset="<?php echo e($image['webp_srcset']); ?>"
                <?php if($sizes): ?> sizes="<?php echo e($sizes); ?>" <?php endif; ?>
            >
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <img
            src="<?php echo e($image['url']); ?>"
            alt="<?php echo e($alt); ?>"
            width="<?php echo e($width); ?>"
            height="<?php echo e($height); ?>"
            loading="<?php echo e($loading); ?>"
            decoding="<?php echo e($decoding); ?>"
            <?php if($fetchpriority): ?> fetchpriority="<?php echo e($fetchpriority); ?>" <?php endif; ?>
            <?php if($sizes): ?> sizes="<?php echo e($sizes); ?>" <?php endif; ?>
            <?php if($class): ?> class="<?php echo e($class); ?>" <?php endif; ?>
        >
    </picture>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/landing/responsive-image.blade.php ENDPATH**/ ?>