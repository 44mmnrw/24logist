@props(['user', 'size' => 'md'])

<span {{ $attributes->class(['community-user-avatar', 'community-user-avatar--'.$size]) }}>
    @if ($user->avatarUrl())
        <img src="{{ $user->avatarUrl() }}" alt="Аватар {{ '@'.$user->username }}" loading="lazy">
    @else
        <span aria-hidden="true">{{ $user->avatarInitial() }}</span>
    @endif
</span>
