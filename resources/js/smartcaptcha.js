let sdkPromise;
const unavailableMessage = 'Не удалось загрузить капчу. Проверьте соединение и повторите попытку.';

function loadSdk() {
    if (window.smartCaptcha) return Promise.resolve(window.smartCaptcha);
    if (sdkPromise) return sdkPromise;

    sdkPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        const callbackName = 'logistruSmartCaptchaLoaded';
        const cleanup = () => {
            window.clearTimeout(timeout);
            delete window[callbackName];
        };
        const fail = () => {
            cleanup();
            script.remove();
            reject(new Error(unavailableMessage));
        };
        const timeout = window.setTimeout(fail, 15000);
        window[callbackName] = () => {
            if (!window.smartCaptcha) return fail();
            cleanup();
            resolve(window.smartCaptcha);
        };
        script.src = `https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=${callbackName}`;
        script.async = true;
        script.onerror = fail;
        document.head.append(script);
    }).catch((error) => {
        sdkPromise = undefined;
        throw error;
    });

    return sdkPromise;
}

export function createSmartCaptcha(form) {
    const root = form?.querySelector('[data-smartcaptcha]');
    if (!root) return { load: async () => {}, getToken: async () => '', reset: () => {} };

    const container = root.querySelector('[data-smartcaptcha-widget]');
    const status = root.querySelector('[data-smartcaptcha-status]');
    const retry = root.querySelector('[data-smartcaptcha-retry]');
    let widgetId;
    let loading;
    let api;
    let widgetError = false;

    const showStatus = (message = '', canRetry = false) => {
        status.textContent = message;
        status.hidden = !message;
        retry.hidden = !canRetry;
    };

    const load = () => {
        if (widgetId !== undefined || root.closest('[hidden]')) return Promise.resolve();
        if (loading) return loading;

        showStatus('Загружаем проверку…');
        loading = (async () => {
            try {
                if (!root.dataset.sitekey) throw new Error(unavailableMessage);
                api = await loadSdk();
                if (root.closest('[hidden]')) return;
                widgetId = api.render(container, {
                    sitekey: root.dataset.sitekey,
                    hl: 'ru',
                    callback: () => showStatus(),
                });
                widgetError = false;
                api.subscribe(widgetId, 'token-expired', () => showStatus('Проверка устарела. Пройдите её ещё раз.'));
                const failWidget = () => {
                    widgetError = true;
                    showStatus(unavailableMessage, true);
                };
                api.subscribe(widgetId, 'network-error', failWidget);
                api.subscribe(widgetId, 'javascript-error', failWidget);
                showStatus();
            } catch {
                showStatus(unavailableMessage, true);
            }
        })().finally(() => { loading = undefined; });

        return loading;
    };

    retry.addEventListener('click', () => {
        if (widgetId !== undefined) api.destroy(widgetId);
        widgetId = undefined;
        load();
    });

    return {
        load,
        async getToken() {
            await load();
            if (widgetId === undefined || widgetError) throw new Error(unavailableMessage);
            const token = api.getResponse(widgetId);
            if (!token) throw new Error('Подтвердите, что вы не робот.');
            return token;
        },
        reset() {
            if (widgetId !== undefined && !widgetError) {
                api.reset(widgetId);
                showStatus();
            }
        },
    };
}
