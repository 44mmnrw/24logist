@php
    $favicon = app(\App\Services\SiteSettingsService::class)->favicon();
    $appleTouchIconUrl = \App\Support\AppleTouchIcon::url();
    $manifestUrl = \App\Support\WebAppManifest::url();
@endphp

<link rel="icon" href="{{ $favicon['root_url'] }}" type="{{ $favicon['type'] }}">
<link rel="shortcut icon" href="{{ $favicon['root_url'] }}" type="{{ $favicon['type'] }}">
@if ($favicon['url'] !== $favicon['root_url'])
    <link rel="icon" href="{{ $favicon['url'] }}" type="{{ $favicon['type'] }}">
@endif
<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchIconUrl }}">
<link rel="manifest" href="{{ $manifestUrl }}">
<meta name="theme-color" content="#1d4ed8">
