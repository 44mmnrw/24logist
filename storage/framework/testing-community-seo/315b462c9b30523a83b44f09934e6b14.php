<?php
    $metrika = app(\App\Services\SiteSettingsService::class)->yandexMetrika();
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($metrika): ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js', 'ym');

        ym(<?php echo e($metrika['counter_id']); ?>, 'init', {
            clickmap: <?php echo json_encode($metrika['clickmap'], 15, 512) ?>,
            trackLinks: <?php echo json_encode($metrika['track_links'], 15, 512) ?>,
            accurateTrackBounce: <?php echo json_encode($metrika['accurate_track_bounce'], 15, 512) ?>,
            webvisor: <?php echo json_encode($metrika['webvisor'], 15, 512) ?>
        });
    </script>
    <noscript>
        <div>
            <img src="https://mc.yandex.ru/watch/<?php echo e($metrika['counter_id']); ?>" style="position:absolute; left:-9999px;" alt="">
        </div>
    </noscript>
    <!-- /Yandex.Metrika counter -->
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\laragon\www\24logistru\resources\views/components/analytics/yandex-metrika.blade.php ENDPATH**/ ?>