<form method="POST" action="{{ route('community.report') }}" class="community-report-form">
    @csrf<input type="hidden" name="target_type" value="{{ $type }}"><input type="hidden" name="target_id" value="{{ $targetId }}">
    <select name="reason" required><option value="">Причина</option><option value="spam">Спам</option><option value="abuse">Оскорбления</option><option value="illegal">Незаконный контент</option><option value="personal_data">Персональные данные</option><option value="other">Другое</option></select>
    <textarea name="details" maxlength="1000" rows="2" placeholder="Комментарий (необязательно)"></textarea>
    <button class="btn btn--sm" type="submit">Отправить</button>
</form>
