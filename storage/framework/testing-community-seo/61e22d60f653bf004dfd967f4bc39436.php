
<?php $__env->startSection('content'); ?>
<div class="landing-shell community-layout community-topic-layout">
    <div class="community-topic-column">
    <article class="community-topic">
        <header>
            <div class="community-author-line">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->author): ?><a class="community-avatar-link" href="<?php echo e(route('community.profile', $post->author)); ?>"><?php if (isset($component)) { $__componentOriginal701c92bfa02fab7fa95abe81787e2da7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.avatar','data' => ['user' => $post->author,'size' => 'md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($post->author),'size' => 'md']); ?>
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
<?php endif; ?></a><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="community-meta">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->author): ?><a href="<?php echo e(route('community.profile', $post->author)); ?>" title="<?php echo e('@'.$post->author->username); ?>"><?php echo e($post->author->displayName()); ?></a><?php else: ?><span>[удалён]</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->author?->transportRoleLabel()): ?><span class="community-author-flair"><?php echo e($post->author->transportRoleLabel()); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span>•</span><time><?php echo e(\App\Support\CommunityDate::relative($post->published_at)); ?></time>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->edited_at): ?><span>• изменено</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <h1><?php echo e($communitySeo['h1']); ?></h1>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->accepted_comment_id): ?><span class="community-badge community-badge--resolved">Есть решение</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="community-topic__labels">
                <a class="community-category-pill" href="<?php echo e(route('community.categories.show', $post->category)); ?>"><?php echo e($post->category->name); ?></a>
            </div>
        </header>
        <div class="community-topic__content">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->external_url): ?>
                <a class="community-link-topic" href="<?php echo e($post->external_url); ?>" rel="ugc nofollow noopener" target="_blank"><span>Открыть ссылку: <?php echo e(parse_url($post->external_url, PHP_URL_HOST)); ?></span><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'external-link','size' => '17']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'external-link','size' => '17']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?></a>
            <?php else: ?>
                <div class="community-markdown"><?php echo $post->body_html; ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php echo $__env->make('community.photos._gallery', ['photos' => $post->photos], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="community-topic__actions">
            <?php echo $__env->make('community.shared._vote', ['type' => 'post', 'target' => $post, 'currentVote' => $postVote, 'variant' => 'inline'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('community.shared._awards', ['type' => 'post', 'target' => $post, 'social' => $postSocial], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('community.shared._reactions', ['type' => 'post', 'target' => $post, 'social' => $postSocial], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <a class="community-action-chip community-action-chip--comments" href="#comments"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'message-circle','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'message-circle','size' => '16']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?><?php echo e($post->comments_count); ?> <?php echo e(\App\Support\CommunityText::comments($post->comments_count)); ?></a>
            <button class="community-action-chip community-action-chip--share" type="button" data-share-url="<?php echo e($post->getUrl()); ?>"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'share-3','size' => '16']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'share-3','size' => '16']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?><span data-share-label>Поделиться</span></button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard('community')->check()): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->accepted_comment_id && ($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())): ?>
                    <form method="POST" action="<?php echo e(route('community.posts.clear_answer', $post)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="community-action-chip" type="submit">Снять решение</button></form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth('community')->user()->isOnboarded()): ?>
                    <form method="POST" action="<?php echo e($subscribed ? route('community.posts.unsubscribe', $post) : route('community.posts.subscribe', $post)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subscribed): ?><?php echo method_field('DELETE'); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <button class="community-action-chip" type="submit"><?php echo e($subscribed ? 'Отписаться от темы' : 'Следить за ответами'); ?></button>
                    </form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator()): ?>
                    <a class="community-action-chip" href="<?php echo e(route('community.posts.edit', $post)); ?>">Изменить</a>
                    <form method="POST" action="<?php echo e(route('community.posts.destroy', $post)); ?>" onsubmit="return confirm('Удалить тему?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="community-action-chip">Удалить</button></form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <button class="community-action-chip" type="button" data-report-open data-report-type="post" data-report-id="<?php echo e($post->id); ?>">Пожаловаться</button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </article>

    <section id="comments" class="community-comments">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->locked_at): ?>
            <div class="community-notice">Обсуждение закрыто модератором.</div>
        <?php elseif(auth('community')->check() && auth('community')->user()->isOnboarded()): ?>
            <?php echo $__env->make('community.comments._form', ['post' => $post, 'parent' => null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <div class="community-notice"><a href="<?php echo e(route('community.login')); ?>">Войдите</a>, чтобы оставить комментарий.</div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="community-comments__toolbar">
            <h2>Комментарии <span><?php echo e($post->comments_count); ?></span></h2>
            <nav aria-label="Сортировка комментариев">
                <span>Сначала:</span>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $commentSort === 'best']); ?>" href="<?php echo e(request()->fullUrlWithQuery(['comment_sort' => 'best', 'page' => null]).'#comments'); ?>">лучшие</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $commentSort === 'new']); ?>" href="<?php echo e(request()->fullUrlWithQuery(['comment_sort' => 'new', 'page' => null]).'#comments'); ?>">новые</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $commentSort === 'old']); ?>" href="<?php echo e(request()->fullUrlWithQuery(['comment_sort' => 'old', 'page' => null]).'#comments'); ?>">старые</a>
            </nav>
        </div>

        <div class="community-comment-list">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $roots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php echo $__env->make('community.comments._comment', ['comment' => $comment, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes, 'commentSocial' => $commentSocial], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="community-empty">Пока нет комментариев. Начните обсуждение.</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <div class="community-pagination"><?php echo e($roots->links()); ?></div>
    </section>
    </div>

    <?php ($communityAboutCard = app(\App\Services\SiteSettingsService::class)->communityAboutCard()); ?>
    <aside class="community-sidebar community-topic-sidebar" aria-label="О сообществе">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($communityAboutCard['enabled']): ?>
            <div class="community-side-card community-about-card">
                <span class="community-side-card__eyebrow"><?php echo e($communityAboutCard['eyebrow']); ?></span>
                <h2><?php echo e($communityAboutCard['title']); ?></h2>
                <p><?php echo e($communityAboutCard['description']); ?></p>
                <dl class="community-about-card__stats">
                    <div><dt><?php echo e(number_format($communityStats['members'], 0, ',', ' ')); ?></dt><dd><?php echo e($communityAboutCard['members_label']); ?></dd></div>
                    <div><dt><?php echo e(number_format($communityStats['topics'], 0, ',', ' ')); ?></dt><dd><?php echo e($communityAboutCard['topics_label']); ?></dd></div>
                </dl>
                <a class="community-side-card__link" href="<?php echo e(route('community.index')); ?>"><?php echo e($communityAboutCard['button_text']); ?></a>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="community-side-card community-rules">
            <h2>Правила</h2>
            <ol>
                <li>Уважайте собеседников.</li>
                <li>Не публикуйте рекламу и персональные данные.</li>
                <li>Подкрепляйте профессиональные советы фактами.</li>
            </ol>
        </div>
    </aside>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard('community')->check()): ?>
        <?php echo $__env->make('community.shared._report_dialog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('community.shared._award_dialog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <dialog class="community-confirm-dialog" data-comment-delete-dialog aria-labelledby="community-comment-delete-title">
            <div class="community-confirm-dialog__icon" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'trash','size' => '22']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','size' => '22']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?></div>
            <div class="community-confirm-dialog__content">
                <h2 id="community-comment-delete-title">Удалить комментарий?</h2>
                <p>Комментарий будет удалён. Отменить это действие не получится.</p>
            </div>
            <button class="community-confirm-dialog__close" type="button" data-comment-delete-cancel aria-label="Закрыть"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'x','size' => '18']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '18']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $attributes = $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc)): ?>
<?php $component = $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc; ?>
<?php unset($__componentOriginal619ce122d97a5e1b1586b601e82fa0cc); ?>
<?php endif; ?></button>
            <div class="community-confirm-dialog__actions">
                <button class="btn btn--ghost btn--sm" type="button" data-comment-delete-cancel>Отмена</button>
                <button class="btn btn--sm community-confirm-dialog__danger" type="button" data-comment-delete-confirm>Удалить</button>
            </div>
        </dialog>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('community.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\24logistru\resources\views/community/posts/show.blade.php ENDPATH**/ ?>