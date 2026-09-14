@php
    $canReact = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && (int) $target->community_user_id !== (int) auth('community')->id();
    $reactionCounts = $social['reactions'] ?? [];
@endphp
@if ($canReact || array_sum($reactionCounts) > 0)
    <div class="community-reactions" @if ($canReact) data-community-reactions data-type="{{ $type }}" data-id="{{ $target->id }}" data-endpoint="{{ route('community.react') }}" @endif aria-label="Реакции">
        @foreach (\App\Services\Community\CommunitySocialService::REACTIONS as $code => $reaction)
            @if ($canReact)
                <button class="community-reaction @if (($social['selected'] ?? null) === $code) is-active @endif" type="button" data-code="{{ $code }}" aria-pressed="{{ ($social['selected'] ?? null) === $code ? 'true' : 'false' }}" title="{{ $reaction['label'] }}">
                    <span class="community-reaction__emoji" aria-hidden="true">{{ $reaction['emoji'] }}</span><span class="community-reaction__label">{{ $reaction['label'] }}</span><span class="community-reaction__count" data-reaction-count="{{ $code }}">{{ $reactionCounts[$code] ?? 0 }}</span>
                </button>
            @elseif (($reactionCounts[$code] ?? 0) > 0)
                <span class="community-reaction" title="{{ $reaction['label'] }}"><span class="community-reaction__emoji" aria-hidden="true">{{ $reaction['emoji'] }}</span><span class="community-reaction__label">{{ $reaction['label'] }}</span><span class="community-reaction__count">{{ $reactionCounts[$code] }}</span></span>
            @endif
        @endforeach
    </div>
@endif
