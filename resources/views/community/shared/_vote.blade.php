@php($canVote = auth('community')->check() && auth('community')->user()->isOnboarded())
<div @class(['community-vote', 'community-vote--inline' => ($variant ?? null) === 'inline']) data-vote data-type="{{ $type }}" data-id="{{ $target->id }}" data-endpoint="{{ route('community.vote') }}">
    <button type="button" data-value="1" @class(['is-active' => (int)$currentVote === 1]) @disabled(!$canVote) aria-label="Поддержать">↑</button>
    <strong data-score>{{ $target->score }}</strong>
    <button type="button" data-value="-1" @class(['is-active' => (int)$currentVote === -1]) @disabled(!$canVote) aria-label="Не поддержать">↓</button>
</div>
