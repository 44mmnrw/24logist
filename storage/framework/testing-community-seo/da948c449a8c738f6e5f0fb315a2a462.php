<?php
    $settings = app(\App\Services\SiteSettingsService::class)->get();
    $enabled = (bool) $settings->telegram_popup_enabled && filled($settings->telegram_popup_channel_url);
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($enabled): ?>
    <div
        class="telegram-popup"
        data-telegram-popup
        data-show-delay="<?php echo e(max(45, (int) $settings->telegram_popup_show_delay)); ?>"
        data-scroll-percent="<?php echo e(min(90, max(25, (int) $settings->telegram_popup_scroll_percent))); ?>"
        data-auto-close-delay="<?php echo e(max(0, (int) $settings->telegram_popup_auto_close_delay)); ?>"
        data-cooldown-days="7"
        role="region"
        aria-live="polite"
        aria-labelledby="telegram-popup-title"
        aria-describedby="telegram-popup-description"
        aria-hidden="true"
        hidden
    >
        <div class="telegram-popup__card" role="document">
            <button class="telegram-popup__close" type="button" data-telegram-popup-close aria-label="Закрыть">×</button>
            <div class="telegram-popup__eyebrow">
                <?php if (isset($component)) { $__componentOriginal4685c1bec61dbcbf70087a04cfe5533b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4685c1bec61dbcbf70087a04cfe5533b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.landing.icon','data' => ['name' => 'icon:telegram','class' => 'telegram-popup__logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('landing.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'icon:telegram','class' => 'telegram-popup__logo']); ?>
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
                <span><?php echo e($settings->telegram_popup_badge); ?></span>
            </div>
            <h2 id="telegram-popup-title"><?php echo e($settings->telegram_popup_title); ?></h2>
            <p id="telegram-popup-description"><?php echo e($settings->telegram_popup_description); ?></p>
            <a
                class="telegram-popup__subscribe"
                href="<?php echo e($settings->telegram_popup_channel_url); ?>"
                data-mobile-url="<?php echo e($settings->telegram_popup_mobile_url); ?>"
                target="_blank"
                rel="noopener noreferrer"
                data-telegram-popup-subscribe
            >
                <span><?php echo e($settings->telegram_popup_button_text); ?></span>
            </a>
            <button class="telegram-popup__later" type="button" data-telegram-popup-close><?php echo e($settings->telegram_popup_dismiss_text); ?></button>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/site/telegram-popup.blade.php ENDPATH**/ ?>