@php
    $favicon = app(\App\Services\SiteSettingsService::class)->favicon();
@endphp

<link rel="icon" href="{{ $favicon['root_url'] }}" type="{{ $favicon['type'] }}">
<link rel="shortcut icon" href="{{ $favicon['root_url'] }}" type="{{ $favicon['type'] }}">
@if ($favicon['url'] !== $favicon['root_url'])
    <link rel="icon" href="{{ $favicon['url'] }}" type="{{ $favicon['type'] }}">
@endif
