@php
    $section = $landing->section('platform');
@endphp

@if ($section)
<section class="platform-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    @php
        $extra = $section?->extra ?? [];
        $cards = $landing->blocks('platform', 'card');
    @endphp

    <div class="landing-shell">
        <header class="section-head">
            @if ($section?->kicker)
                <span class="section-kicker">{{ $section->kicker }}</span>
            @endif
            @if ($section?->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section?->description)
                <p>{{ $section->description }}</p>
            @endif
        </header>

        <div class="platform-grid">
            @foreach ($cards as $card)
                <article class="platform-card">
                    @if ($card->tag)
                        <span class="platform-tag">{{ $card->tag }}</span>
                    @endif

                    <div class="platform-card__head">
                        @if ($card->icon)
                            <div class="platform-card__icon">
                                <x-landing.icon :name="$card->icon" />
                            </div>
                        @endif
                        @if ($card->subtitle)
                            <span class="platform-card__num">{{ $card->subtitle }}</span>
                        @endif
                    </div>

                    <h3>{{ $card->title }}</h3>
                    <p>{{ $card->description }}</p>

                    @foreach ($card->children->where('block_type', 'note') as $note)
                        <div class="platform-note">
                            @if ($note->icon)
                                <x-landing.icon :name="$note->icon" />
                            @endif
                            <span>{!! $note->description !!}</span>
                        </div>
                    @endforeach

                    @if ($card->children->where('block_type', 'list_item')->isNotEmpty())
                        <ul class="platform-list">
                            @foreach ($card->children->where('block_type', 'list_item') as $item)
                                <li>
                                    @if ($item->icon)
                                        <x-landing.icon :name="$item->icon" />
                                    @endif
                                    {{ $item->title }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($card->children->where('block_type', 'pill')->isNotEmpty())
                        <div class="platform-pills">
                            @foreach ($card->children->where('block_type', 'pill') as $pill)
                                <span>{{ $pill->title }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if ($card->children->where('block_type', 'role')->isNotEmpty())
                        <div class="platform-roles">
                            @foreach ($card->children->where('block_type', 'role') as $role)
                                <div><b>{{ $role->title }}</b><span>{{ $role->subtitle }}</span></div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if (! empty($extra['deadline_text']))
            <div class="deadline-banner">
                <div class="deadline-badge">
                    <span class="deadline-icon">
                        @if (! empty($extra['deadline_icon']))
                            <x-landing.icon :name="$extra['deadline_icon']" />
                        @endif
                    </span>
                    <div>
                        @if (! empty($extra['deadline_kicker']))
                            <small>{{ $extra['deadline_kicker'] }}</small>
                        @endif
                        @if (! empty($extra['deadline_date']))
                            <strong>{{ $extra['deadline_date'] }}</strong>
                        @endif
                    </div>
                </div>
                <p>{{ $extra['deadline_text'] }}</p>
                @if (! empty($extra['deadline_button_text']))
                    <button type="button" class="btn btn--primary btn--sm">{{ $extra['deadline_button_text'] }}</button>
                @endif
            </div>
        @endif
    </div>
</section>
@endif
