const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-vote] button[data-value]');
    if (!button || button.disabled) return;

    const widget = button.closest('[data-vote]');
    const requested = Number(button.dataset.value);
    const value = button.classList.contains('is-active') ? 0 : requested;
    button.disabled = true;

    try {
        const response = await fetch(widget.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({target_type: widget.dataset.type, target_id: Number(widget.dataset.id), value}),
        });
        if (!response.ok) throw new Error('vote_failed');
        const result = await response.json();
        widget.querySelector('[data-score]').textContent = result.score;
        widget.querySelectorAll('button[data-value]').forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.value) === result.user_vote);
        });
    } catch (_) {
        window.alert('Не удалось сохранить голос. Обновите страницу и попробуйте ещё раз.');
    } finally {
        button.disabled = false;
    }
});

const maxAuth = document.querySelector('[data-max-auth]');

if (maxAuth) {
    const statusNode = maxAuth.querySelector('[data-max-status]');
    const post = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result?.message || 'MAX auth failed');
        return result;
    };

    const initData = window.WebApp?.initData;
    const startParam = window.WebApp?.initDataUnsafe?.start_param;

    if (initData) {
        statusNode.textContent = 'Подтверждаем данные MAX…';
        post(startParam ? maxAuth.dataset.approveUrl : maxAuth.dataset.sessionUrl,
            startParam ? {challenge: startParam, init_data: initData} : {init_data: initData})
            .then((result) => {
                if (result.redirect) window.location.assign(result.redirect);
                else statusNode.textContent = 'Вход подтверждён. Вернитесь в браузер.';
            })
            .catch(() => { statusNode.textContent = 'Не удалось подтвердить вход. Откройте ссылку заново.'; });
    } else {
        const startedAt = Date.now();
        const poll = window.setInterval(async () => {
            if (Date.now() - startedAt > 310000) {
                window.clearInterval(poll);
                statusNode.textContent = 'Ссылка истекла. Обновите страницу.';
                return;
            }
            try {
                const response = await fetch(maxAuth.dataset.statusUrl, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
                const result = await response.json();
                if (result.redirect) {
                    window.clearInterval(poll);
                    window.location.assign(result.redirect);
                } else if (result.status === 'expired') {
                    window.clearInterval(poll);
                    statusNode.textContent = 'Ссылка истекла. Обновите страницу.';
                }
            } catch (_) {}
        }, 2000);
    }
}
