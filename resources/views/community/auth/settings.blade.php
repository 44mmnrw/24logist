@extends('community.layout')
@section('title', 'Настройки профиля — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <div class="community-form-card">
        <h1>Настройки профиля</h1>
        <p>Публичный псевдоним: <strong>{{ '@'.$user->username }}</strong></p>
        <form method="POST" action="{{ route('community.settings.update') }}" class="community-form">@csrf @method('PUT')
            <h2>Связанные аккаунты</h2>
            @php
                $providers = [
                    'telegram' => ['label' => 'Telegram', 'notifications' => true, 'enabled' => true, 'url' => route('community.auth.telegram.redirect', ['notify' => 1])],
                    'max' => ['label' => 'MAX', 'notifications' => true, 'enabled' => app(\App\Services\SiteSettingsService::class)->communityMaxEnabled(), 'url' => route('community.auth.max.start')],
                    'vk' => ['label' => 'VK ID', 'notifications' => false, 'enabled' => app(\App\Services\SiteSettingsService::class)->communityVkEnabled(), 'url' => route('community.auth.vk.redirect')],
                ];
            @endphp
            @foreach ($providers as $provider => $providerData)
                @php($identity = $user->identities->firstWhere('provider', $provider))
                <div class="community-identity">
                    <strong>{{ $providerData['label'] }}</strong>
                    @if ($identity)
                        <span>Привязан</span>
                        @if ($providerData['notifications'])
                            <label class="community-check"><input type="checkbox" name="{{ $provider }}_notifications" value="1" @checked($identity->notifications_enabled) @disabled(!$identity->bot_access)> Уведомлять об ответах</label>
                        @endif
                        @if ($providerData['notifications'] && !$identity->bot_access)
                            <small>Чтобы включить сообщения, повторно подтвердите доступ бота.</small>
                            @if ($provider === 'telegram')<a href="{{ route('community.auth.telegram.redirect', ['notify' => 1]) }}">Разрешить уведомления</a>@endif
                        @endif
                    @else
                        @if ($providerData['enabled'])<a href="{{ $providerData['url'] }}">Привязать</a>@endif
                    @endif
                </div>
            @endforeach
            <button class="btn btn--primary" type="submit">Сохранить</button>
        </form>
    </div>
    <div class="community-form-card community-danger-zone">
        <h2>Удаление аккаунта</h2><p>Связанные внешние идентификаторы и настройки будут удалены, публикации — обезличены.</p>
        <form method="POST" action="{{ route('community.settings.destroy') }}">@csrf @method('DELETE')<label>Введите «УДАЛИТЬ»<input name="confirmation" required></label><button class="community-danger" type="submit">Удалить аккаунт</button></form>
    </div>
</div>
@endsection
