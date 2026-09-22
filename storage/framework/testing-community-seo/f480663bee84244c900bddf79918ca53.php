<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'size' => 18, 'filled' => false]));

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

foreach (array_filter((['name', 'size' => 18, 'filled' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<svg <?php echo e($attributes->class(['community-icon'])); ?> xmlns="http://www.w3.org/2000/svg" width="<?php echo e($size); ?>" height="<?php echo e($size); ?>" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <use href="<?php echo e('/icons/tabler/v3.46.0/tabler-sprite'.($filled ? '-filled' : '').'.svg#tabler-'.($filled ? 'filled-' : '').$name); ?>" />
</svg>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/community/icon.blade.php ENDPATH**/ ?>