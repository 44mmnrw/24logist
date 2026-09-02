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

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-url]');
    if (!button) return;

    const url = button.dataset.shareUrl;
    const label = button.querySelector('[data-share-label]');

    try {
        if (navigator.share) {
            await navigator.share({title: document.title, url});
            return;
        }

        await navigator.clipboard.writeText(url);
        if (label) {
            const previous = label.textContent;
            label.textContent = 'Ссылка скопирована';
            window.setTimeout(() => { label.textContent = previous; }, 1800);
        }
    } catch (_) {}
});

const reportDialog = document.querySelector('[data-report-dialog]');

if (reportDialog) {
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-report-open]');
        const closeButton = event.target.closest('[data-report-close]');

        if (openButton) {
            const form = reportDialog.querySelector('form');
            form.reset();
            form.querySelector('[data-report-target-type]').value = openButton.dataset.reportType;
            form.querySelector('[data-report-target-id]').value = openButton.dataset.reportId;

            if (typeof reportDialog.showModal === 'function') reportDialog.showModal();
            else reportDialog.setAttribute('open', '');
        } else if (closeButton) {
            if (typeof reportDialog.close === 'function') reportDialog.close();
            else reportDialog.removeAttribute('open');
        }
    });

    reportDialog.addEventListener('click', (event) => {
        if (event.target === reportDialog) reportDialog.close();
    });
}

const maxMiniApp = document.querySelector('[data-max-mini-app]');

if (maxMiniApp) {
    const returnButton = maxMiniApp.querySelector('[data-max-return]');
    const statusNode = maxMiniApp.querySelector('[data-max-mini-status]');
    const initData = window.WebApp?.initData;
    const startParam = window.WebApp?.initDataUnsafe?.start_param;

    const post = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result?.message || 'MAX auth failed');
        return result;
    };

    if (!initData) {
        statusNode.textContent = 'Откройте эту страницу внутри приложения MAX.';
    } else {
        const completeLogin = async () => {
            const hasChallenge = startParam && startParam !== 'community-login';
            try {
                const result = await post(
                    hasChallenge ? maxMiniApp.dataset.approveUrl : maxMiniApp.dataset.sessionUrl,
                    hasChallenge ? {challenge: startParam, init_data: initData} : {init_data: initData},
                );

                returnButton.href = result.return_url;
                returnButton.hidden = false;
                statusNode.textContent = 'Аккаунт MAX подтверждён. Нажмите «Авторизоваться».';
            } catch (_) {
                statusNode.textContent = 'Не удалось подтвердить вход. Откройте ссылку заново.';
            }
        };

        completeLogin();
    }

    returnButton.addEventListener('click', (event) => {
        const platform = window.WebApp?.platform;

        if (platform === 'web' || typeof window.WebApp?.openLink !== 'function') return;

        event.preventDefault();
        window.WebApp.openLink(returnButton.href);
    });
}
