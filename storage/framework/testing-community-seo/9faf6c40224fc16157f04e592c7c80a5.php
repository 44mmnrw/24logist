<?php ($commentChildren = $children->get($comment->id, collect())); ?>
<article id="comment-<?php echo e($comment->id); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['community-comment', 'community-comment--has-children' => $commentChildren->isNotEmpty()]); ?>" style="--comment-depth: <?php echo e($comment->depth); ?>">
    <div class="community-comment__body">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->author): ?><a class="community-avatar-link" href="<?php echo e(route('community.profile', $comment->author)); ?>"><?php if (isset($component)) { $__componentOriginal701c92bfa02fab7fa95abe81787e2da7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal701c92bfa02fab7fa95abe81787e2da7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.avatar','data' => ['user' => $comment->author,'size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($comment->author),'size' => 'sm']); ?>
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
<?php endif; ?></a><?php else: ?><span class="community-user-avatar community-user-avatar--sm">?</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="community-comment__content">
            <div class="community-meta">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->author): ?><a href="<?php echo e(route('community.profile', $comment->author)); ?>" title="<?php echo e('@'.$comment->author->username); ?>"><?php echo e($comment->author->displayName()); ?></a><?php else: ?><span>[удалён]</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->author?->transportRoleLabel()): ?><span class="community-author-flair"><?php echo e($comment->author->transportRoleLabel()); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <span>•</span><time><?php echo e(\App\Support\CommunityDate::relative($comment->created_at)); ?></time><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->edited_at): ?><span>• изменено</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'deleted'): ?>
                <p class="community-deleted">Комментарий удалён автором.</p>
            <?php elseif($comment->status === 'hidden'): ?>
                <p class="community-deleted">Комментарий скрыт модератором.</p>
            <?php else: ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->body_html): ?><div class="community-markdown"><?php echo $comment->body_html; ?></div><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php echo $__env->make('community.photos._gallery', ['photos' => $comment->photos, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($post->accepted_comment_id === $comment->id): ?><span class="community-badge community-badge--resolved">Принятый ответ</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="community-comment__actions">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published'): ?><?php echo $__env->make('community.shared._vote', ['type' => 'comment', 'target' => $comment, 'currentVote' => $commentVotes->get($comment->id), 'variant' => 'inline'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published'): ?><?php echo $__env->make('community.shared._awards', ['type' => 'comment', 'target' => $comment, 'social' => $commentSocial[$comment->id] ?? []], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published'): ?><?php echo $__env->make('community.shared._reactions', ['type' => 'comment', 'target' => $comment, 'social' => $commentSocial[$comment->id] ?? []], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <button class="community-action-button community-action-share" type="button" data-share-url="<?php echo e($post->getUrl().'#comment-'.$comment->id); ?>"><?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['name' => 'share-3','size' => '15']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'share-3','size' => '15']); ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published' && $post->accepted_comment_id !== $comment->id && ($post->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())): ?>
                        <form method="POST" action="<?php echo e(route('community.posts.accept_answer', [$post, $comment])); ?>"><?php echo csrf_field(); ?><button type="submit">Отметить решением</button></form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published' && !$post->locked_at && $comment->depth < config('community.limits.comment_depth') - 1): ?>
                        <details><summary>Ответить</summary><?php echo $__env->make('community.comments._form', ['post' => $post, 'parent' => $comment], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></details>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status !== 'deleted' && ($comment->community_user_id === auth('community')->id() || auth('community')->user()->isModerator())): ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published'): ?>
                            <details><summary>Изменить</summary>
                                <form method="POST" action="<?php echo e(route('community.comments.update', $comment)); ?>" class="community-comment-form" enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                    <div class="community-rich-editor" data-rich-editor>
                                        <?php echo $__env->make('community.comments._format_toolbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <textarea name="body_markdown" maxlength="5000" rows="3"><?php echo e($comment->body_markdown); ?></textarea>
                                        <div class="community-rich-editor__surface" data-rich-editor-surface hidden></div>
                                    </div>
                                    <?php echo $__env->make('community.photos._editor', ['existingPhotos' => $comment->photos], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <button class="btn btn--sm">Сохранить</button>
                                </form>
                            </details>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <form method="POST" action="<?php echo e(route('community.comments.destroy', $comment)); ?>" data-comment-delete-form><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button type="submit">Удалить</button></form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($comment->status === 'published'): ?><button class="community-action-button" type="button" data-report-open data-report-type="comment" data-report-id="<?php echo e($comment->id); ?>">Пожаловаться</button><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($commentChildren->isNotEmpty()): ?>
        <div class="community-comment__children" id="comment-children-<?php echo e($comment->id); ?>" data-comment-children>
            <button class="community-thread-toggle" type="button" data-thread-toggle aria-expanded="true" aria-controls="comment-children-<?php echo e($comment->id); ?>" aria-label="Свернуть ветку">
                <?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['class' => 'community-thread-toggle__minus','name' => 'minus','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'community-thread-toggle__minus','name' => 'minus','size' => '14']); ?>
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
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal619ce122d97a5e1b1586b601e82fa0cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal619ce122d97a5e1b1586b601e82fa0cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.community.icon','data' => ['class' => 'community-thread-toggle__plus','name' => 'plus','size' => '14']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('community.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'community-thread-toggle__plus','name' => 'plus','size' => '14']); ?>
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
<?php endif; ?>
            </button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $commentChildren; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php echo $__env->make('community.comments._comment', ['comment' => $child, 'post' => $post, 'children' => $children, 'commentVotes' => $commentVotes], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</article>
<?php /**PATH C:\laragon\www\24logistru\resources\views/community/comments/_comment.blade.php ENDPATH**/ ?>