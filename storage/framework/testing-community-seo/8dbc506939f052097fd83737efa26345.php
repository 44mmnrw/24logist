<?php $__env->startSection('title', 'Правила сообщества 24Logist'); ?>
<?php $__env->startSection('description', 'Правила публикации тем и комментариев в сообществе 24Logist.'); ?>

<?php $__env->startSection('content'); ?>
<div class="landing-shell community-form-shell"><article class="community-form-card community-markdown">
    <h1><?php echo e($communitySeo['h1']); ?></h1>
    <p>Сообщество создано для профессионального и уважительного обмена опытом в логистике.</p>
    <h2>Что приветствуется</h2><ul><li>Практические вопросы и подробные ответы.</li><li>Ссылки на первоисточники и описание собственного опыта.</li><li>Конструктивное несогласие без перехода на личности.</li></ul>
    <h2>Что запрещено</h2><ul><li>Спам, скрытая реклама и массовые повторные публикации.</li><li>Оскорбления, угрозы, дискриминация и преследование.</li><li>Публикация чужих персональных данных и конфиденциальных документов.</li><li>Незаконный контент и инструкции по обходу закона.</li></ul>
    <p>Модераторы могут скрывать материалы, закрывать темы и временно или постоянно ограничивать нарушителей. Для обжалования решения используйте контакты, указанные на сайте.</p>
</article></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('community.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\24logistru\resources\views/community/legal/rules.blade.php ENDPATH**/ ?>