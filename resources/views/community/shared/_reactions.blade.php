@php
    $canReact = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && (int) $target->community_user_id !== (int) auth('community')->id();
    $reactionCounts = $social['reactions'] ?? [];
    $selectedReactions = array_values((array) ($social['selected'] ?? []));
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
        >
            <span class="community-reaction-trigger__icons" data-reaction-trigger-icons>
                @forelse ($selectedReactions as $selectedCode)
                    <span class="community-reaction-chip">
                        <span class="community-reaction__emoji" aria-hidden="true">{{ \App\Services\Community\CommunitySocialService::REACTIONS[$selectedCode]['emoji'] }}</span>
                        <span class="community-reaction-chip__label">{{ \App\Services\Community\CommunitySocialService::REACTIONS[$selectedCode]['label'] }}</span>
                    </span>
                @empty
                    <span class="community-reaction-chip community-reaction-chip--empty">
                        <span class="community-reaction__emoji" aria-hidden="true">👍</span>
                    </span>
                @endforelse
            </span>
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
