@extends('community.layout')
@section('title', 'Настройки профиля — Сообщество 24Logist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="landing-shell community-form-shell">
    <div class="community-form-card">
        <h1>Настройки профиля</h1>
        <form method="POST" action="{{ route('community.settings.update') }}" class="community-form" enctype="multipart/form-data">@csrf @method('PUT')
            <h2>Основные данные</h2>
            <label>
                ID профиля
                <input value="{{ '@'.$user->username }}" readonly aria-readonly="true">
                <small>Уникальный ID используется в адресе профиля и не изменяется.</small>
            </label>
            <label>
                Никнейм
                <input name="display_name" value="{{ old('display_name', $user->displayName()) }}" minlength="2" maxlength="50" required autocomplete="nickname">
                <small>Это имя видят другие участники сообщества.</small>
            </label>
            <label>
                Роль в перевозках
                <select name="transport_role">
                    <option value="">Не указана</option>
                    @foreach (\App\Models\CommunityUser::TRANSPORT_ROLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('transport_role', $user->transport_role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                О себе
                <textarea name="bio" rows="5" maxlength="1000" placeholder="Расскажите о своём опыте, специализации или географии перевозок…">{{ old('bio', $user->bio) }}</textarea>
                <small>До 1000 символов. Контактные и персональные данные лучше не публиковать.</small>
            </label>
            <h2>Фото профиля</h2>
            <div class="community-avatar-setting">
                <x-community.avatar :user="$user" size="lg" />
                <div>
                    <label for="community-avatar-upload">Заменить фото</label>
                    <input id="community-avatar-upload" type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
                    <small>JPG, PNG или WebP, до 3 МБ. После входа берём фото из социальной сети, но загруженный вами аватар больше не перезаписываем.</small>
                    @if ($user->avatar_path)
                        <label class="community-check"><input type="checkbox" name="remove_avatar" value="1"><span>Удалить текущее фото</span></label>
                    @endif
                </div>
            </div>
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
                            <label class="community-check"><input type="checkbox" name="{{ $provider }}_notifications" value="1" @checked($identity->notifications_enabled) @disabled(!$identity->bot_access)><span>Уведомлять об ответах</span></label>
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
