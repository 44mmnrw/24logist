<?php
    $favicon = app(\App\Services\SiteSettingsService::class)->favicon();
    $appleTouchIconUrl = \App\Support\AppleTouchIcon::url();
    $manifestUrl = \App\Support\WebAppManifest::url();
?>

<link rel="icon" href="<?php echo e($favicon['root_url']); ?>" type="<?php echo e($favicon['type']); ?>">
<link rel="shortcut icon" href="<?php echo e($favicon['root_url']); ?>" type="<?php echo e($favicon['type']); ?>">
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($favicon['url'] !== $favicon['root_url']): ?>
    <link rel="icon" href="<?php echo e($favicon['url']); ?>" type="<?php echo e($favicon['type']); ?>">
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo e($appleTouchIconUrl); ?>">
<link rel="manifest" href="<?php echo e($manifestUrl); ?>">
<meta name="theme-color" content="#1d4ed8">
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/site/favicon.blade.php ENDPATH**/ ?>