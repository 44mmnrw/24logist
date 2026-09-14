@if (auth('community')->check() && auth('community')->user()->isOnboarded())
    <dialog class="community-award-dialog" data-award-dialog aria-labelledby="community-award-title">
        <div class="community-award-dialog__top">
            <h2 id="community-award-title">Наградить участника</h2>
            <button class="community-award-dialog__close" type="button" data-award-close aria-label="Закрыть"><x-community.icon name="x" size="20" /></button>
        </div>
        <p class="community-award-dialog__target" data-award-target></p>
        <form method="POST" action="{{ route('community.award') }}">
            @csrf
            <input type="hidden" name="target_type" data-award-type>
            <input type="hidden" name="target_id" data-award-id>
            <fieldset class="community-award-grid">
                <legend>Выберите награду</legend>
                @foreach (\App\Services\Community\CommunitySocialService::AWARDS as $code => $award)
                    <label class="community-award-choice">
                        <input type="radio" name="code" value="{{ $code }}" required @checked($loop->first)>
                        <span class="community-award-choice__body"><span class="community-award-choice__emoji" aria-hidden="true">{{ $award['emoji'] }}</span><span class="community-award-choice__name">{{ $award['label'] }}</span><span class="community-award-choice__free">Бесплатно</span></span>
                    </label>
                @endforeach
            </fieldset>
            <label class="community-award-dialog__anonymous"><input type="checkbox" name="is_anonymous" value="1"> Отправить анонимно</label>
            <label class="community-award-dialog__message" for="community-award-message">Сообщение к награде <span>необязательно</span></label>
            <textarea id="community-award-message" name="message" maxlength="100" rows="2" placeholder="Поблагодарите автора несколькими словами" data-award-message></textarea>
            <div class="community-award-dialog__bottom"><small><span data-award-length>0</span>/100</small><button class="btn btn--primary" type="submit">Вручить награду</button></div>
        </form>
    </dialog>
@endif
