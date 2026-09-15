@php
    $canReact = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && (int) $target->community_user_id !== (int) auth('community')->id();
    $reactionCounts = $social['reactions'] ?? [];
    $selectedReactions = array_values((array) ($social['selected'] ?? []));
    $reactionTotal = array_sum($reactionCounts);
    $firstSelected = $selectedReactions[0] ?? null;
    $triggerReaction = $firstSelected !== null
        ? \App\Services\Community\CommunitySocialService::REACTIONS[$firstSelected]
        : array_values(\App\Services\Community\CommunitySocialService::REACTIONS)[0];
    $pickerId = 'community-reactions-'.$type.'-'.$target->id;
@endphp
@if ($canReact || $reactionTotal > 0)
    <details
        class="community-reactions"
        @if ($canReact)
            data-community-reactions
            data-type="{{ $type }}"
            data-id="{{ $target->id }}"
            data-endpoint="{{ route('community.react') }}"
        @endif
        aria-label="Реакции"
    >
        <summary
            class="community-reaction-trigger @if ($selectedReactions !== []) is-active @endif"
            data-reaction-toggle
        >
            <span class="community-reaction__emoji" data-reaction-trigger-emoji aria-hidden="true">{{ $triggerReaction['emoji'] }}</span>
            <span class="community-reaction-trigger__label" data-reaction-trigger-label>{{ $triggerReaction['label'] }}</span>
            <span class="community-reaction__count @if ($reactionTotal === 0) is-empty @endif" data-reaction-total>{{ $reactionTotal }}</span>
        </summary>

        <div class="community-reaction-picker" id="{{ $pickerId }}" data-reaction-picker role="menu">
        @foreach (\App\Services\Community\CommunitySocialService::REACTIONS as $code => $reaction)
            @php $isSelected = in_array($code, $selectedReactions, true); @endphp
            @if ($canReact)
                <button
                    class="community-reaction-option @if ($isSelected) is-active @endif"
                    type="button"
                    data-code="{{ $code }}"
                    data-emoji="{{ $reaction['emoji'] }}"
                    data-label="{{ $reaction['label'] }}"
                    role="menuitemcheckbox"
                    aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                    title="{{ $reaction['label'] }}"
                >
                    <span class="community-reaction__emoji" aria-hidden="true">{{ $reaction['emoji'] }}</span>
                    <span class="community-reaction-option__label">{{ $reaction['label'] }}</span>
                    <span class="community-reaction__count" data-reaction-count="{{ $code }}">{{ $reactionCounts[$code] ?? 0 }}</span>
                </button>
            @elseif (($reactionCounts[$code] ?? 0) > 0)
                <span class="community-reaction-option is-readonly" role="menuitem">
                    <span class="community-reaction__emoji" aria-hidden="true">{{ $reaction['emoji'] }}</span>
                    <span class="community-reaction-option__label">{{ $reaction['label'] }}</span>
                    <span class="community-reaction__count">{{ $reactionCounts[$code] }}</span>
                </span>
            @endif
        @endforeach
        </div>
    </details>
@endif
