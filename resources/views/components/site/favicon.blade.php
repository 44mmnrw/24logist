@php
    $favicon = app(\App\Services\SiteSettingsService::class)->favicon();
@endphp

<link rel="icon" href="{{ $favicon['url'] }}" type="{{ $favicon['type'] }}">
