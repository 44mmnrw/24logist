@php
    $awardCounts = $social['awards'] ?? [];
    $canAward = auth('community')->check()
        && auth('community')->user()->isOnboarded()
        && !auth('community')->user()->isRestricted()
        && $target->community_user_id !== null
        && (int) $target->community_user_id !== (int) auth('community')->id();
@endphp
@if ($canAward || array_sum($awardCounts) > 0)
    <div class="community-awards" aria-label="Награды">
        @foreach (\App\Services\Community\CommunitySocialService::AWARDS as $code => $award)
            @if (($awardCounts[$code] ?? 0) > 0)
                <span class="community-award-badge" title="{{ $award['label'] }}"><span aria-hidden="true">{{ $award['emoji'] }}</span><span>{{ $awardCounts[$code] }}</span></span>
            @endif
        @endforeach
        @if ($canAward)
            <button class="community-award-open" type="button" data-award-open data-award-type="{{ $type }}" data-award-id="{{ $target->id }}" data-award-title="{{ $type === 'post' ? $target->title : 'Автор: '.($target->author?->displayName() ?? 'участник') }}" aria-label="Наградить {{ $type === 'post' ? 'автора темы' : 'автора комментария' }}"><x-community.icon name="trophy" size="16" /><span>Наградить</span></button>
        @endif
    </div>
@endif
