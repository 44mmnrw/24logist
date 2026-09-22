<?php $__env->startSection('title', 'Создание профиля — Сообщество 24Logist'); ?>
<?php $__env->startSection('robots', 'noindex, nofollow'); ?>

<?php $__env->startSection('content'); ?>
<div class="landing-shell community-auth-shell">
    <div class="community-auth-card community-onboarding-card">
        <span class="section-kicker">Последний шаг</span>
        <h1><?php echo e($communitySeo['h1']); ?></h1>
        <div class="community-onboarding-avatar">
            <?php if (isset($component)) { $__componentOriginal701c92bfa02fab7fa95abe81787e2da7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.avatar','data' => ['user' => $user,'size' => 'lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'size' => 'lg']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal701c92bfa02fab7fa95abe81787e2da7)): ?>
<?php $attributes = $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7; ?>
<?php unset($__attributesOriginal701c92bfa02fab7fa95abe81787e2da7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal701c92bfa02fab7fa95abe81787e2da7)): ?>
<?php $component = $__componentOriginal701c92bfa02fab7fa95abe81787e2da7; ?>
<?php unset($__componentOriginal701c92bfa02fab7fa95abe81787e2da7); ?>
<?php endif; ?>
            <span>Это фото будет видно другим участникам. Заменить или удалить его можно в настройках профиля.</span>
        </div>
        <form method="POST" action="<?php echo e(route('community.onboarding.store')); ?>" class="community-form">
            <?php echo csrf_field(); ?>
            <label for="community-username">
                <span>Псевдоним</span>
                <input id="community-username" name="username" value="<?php echo e(old('username')); ?>" minlength="3" maxlength="30" pattern="[A-Za-zА-Яа-яЁё0-9_-]+" required autocomplete="nickname">
                <small>3–30 символов: буквы, цифры, дефис или подчёркивание.</small>
            </label>
            <label for="community-transport-role">
                <span>Роль в перевозках</span>
                <select id="community-transport-role" name="transport_role" required>
                    <option value="" disabled <?php if(old('transport_role') === null): echo 'selected'; endif; ?>>Выберите роль</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Models\CommunityUser::TRANSPORT_ROLES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($value); ?>" <?php if(old('transport_role') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </label>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $user->identities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $identity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($identity->bot_access): ?>
                    <label class="community-check">
                        <input type="hidden" name="<?php echo e($identity->provider); ?>_notifications" value="0">
                        <input type="checkbox" name="<?php echo e($identity->provider); ?>_notifications" value="1" <?php if((bool) old($identity->provider.'_notifications', true)): echo 'checked'; endif; ?>>
                        <span>Получать ответы через <?php echo e($identity->provider === 'telegram' ? 'Telegram' : 'MAX'); ?></span>
                    </label>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <label class="community-check community-consent">
                <input type="checkbox" name="accept_terms" value="1" required <?php if((bool) old('accept_terms', true)): echo 'checked'; endif; ?>>
                <span>Я принимаю <a href="<?php echo e(route('community.rules')); ?>" target="_blank" rel="noopener">правила сообщества</a> и <a href="<?php echo e(route('community.privacy')); ?>" target="_blank" rel="noopener">политику конфиденциальности</a></span>
            </label>
            <button class="btn btn--primary" type="submit">Открыть сообщество</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('community.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\24logistru\resources\views/community/auth/onboarding.blade.php ENDPATH**/ ?>