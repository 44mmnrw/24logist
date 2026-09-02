<dialog class="community-report-dialog" data-report-dialog aria-labelledby="community-report-title">
    <div class="community-report-dialog__header">
        <div>
            <span class="section-kicker">Обращение модераторам</span>
            <h2 id="community-report-title">Пожаловаться</h2>
        </div>
        <button class="community-report-dialog__close" type="button" data-report-close aria-label="Закрыть">×</button>
    </div>

    <form method="POST" action="{{ route('community.report') }}" class="community-report-form">
        @csrf
        <input type="hidden" name="target_type" value="" data-report-target-type>
        <input type="hidden" name="target_id" value="" data-report-target-id>

        <label>
            <span>Что случилось?</span>
            <select name="reason" required>
                <option value="">Выберите причину</option>
                <option value="spam">Спам или реклама</option>
                <option value="abuse">Оскорбления</option>
                <option value="illegal">Незаконный материал</option>
                <option value="personal_data">Персональные данные</option>
                <option value="other">Другое</option>
            </select>
        </label>

        <label>
            <span>Комментарий <small>(необязательно)</small></span>
            <textarea name="details" maxlength="1000" rows="4" placeholder="Коротко опишите проблему"></textarea>
        </label>

        <div class="community-report-dialog__actions">
            <button class="btn btn--sm" type="button" data-report-close>Отмена</button>
            <button class="btn btn--primary btn--sm" type="submit">Отправить жалобу</button>
        </div>
    </form>
</dialog>
