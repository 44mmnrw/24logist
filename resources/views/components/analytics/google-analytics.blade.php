@php
    $measurementId = app(\App\Services\SiteSettingsService::class)->googleAnalyticsMeasurementId();
@endphp

@if ($measurementId)
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($measurementId) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($measurementId));
    </script>
@endif
