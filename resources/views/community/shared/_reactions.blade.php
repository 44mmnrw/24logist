@php
    $canReact = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && (int) $target->community_user_id !== (int) auth('community')->id();
    $reactionCounts = $social['reactions'] ?? [];
    $selectedReactions = array_values((array) ($social['selected'] ?? []));
    $firstSelected = $selectedReactions[0] ?? null;
    $triggerReaction = $firstSelected !== null
        ? \App\Services\Community\CommunitySocialService::REACTIONS[$firstSelected]
        : array_values(\App\Services\Community\CommunitySocialService::REACTIONS)[0];
    $pickerId = 'community-reactions-'.$type.'-'.$target->id;
@endphp
@if ($canReact || array_sum($reactionCounts) > 0)
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
            aria-label="Выбрать реакцию"
            title="Выбрать реакцию"
        >
            <span class="community-reaction-trigger__icons" data-reaction-trigger-icons aria-hidden="true">
                @forelse ($selectedReactions as $selectedCode)
                    <span class="community-reaction__emoji">{{ \App\Services\Community\CommunitySocialService::REACTIONS[$selectedCode]['emoji'] }}</span>
                @empty
                    <span class="community-reaction__emoji">👍</span>
                @endforelse
            </span>
            <span class="community-reaction-trigger__label @if ($selectedReactions === []) is-empty @endif" data-reaction-trigger-label>{{ $triggerReaction['label'] }}</span>
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
