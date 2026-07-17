const popup = document.querySelector('[data-telegram-popup]');

if (popup) {
    const sessionKey = 'telegram-popup-shown';
    const showDelay = Math.max(0, Number(popup.dataset.showDelay) || 0) * 1000;
    const autoCloseDelay = Math.max(0, Number(popup.dataset.autoCloseDelay) || 0) * 1000;
    let autoCloseTimer;
    let previousFocus;

    const subscribeLink = popup.querySelector('[data-telegram-popup-subscribe]');
    const isMobileDevice = window.navigator.userAgentData?.mobile === true
        || /Android|iPhone|iPad|iPod|Mobile/i.test(window.navigator.userAgent)
        || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

    if (isMobileDevice && subscribeLink?.dataset.mobileUrl?.startsWith('tg://resolve?domain=')) {
        subscribeLink.href = subscribeLink.dataset.mobileUrl;
        subscribeLink.removeAttribute('target');
    }

    const wasShown = () => {
        try {
            return window.sessionStorage.getItem(sessionKey) === '1';
        } catch {
            return false;
        }
    };

    const rememberShown = () => {
        try {
            window.sessionStorage.setItem(sessionKey, '1');
        } catch {
            // Storage can be unavailable in strict privacy modes.
        }
    };

    const close = () => {
        window.clearTimeout(autoCloseTimer);
        popup.classList.remove('is-visible');
        popup.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('telegram-popup-open');

        window.setTimeout(() => {
            popup.hidden = true;
        }, 200);

        previousFocus?.focus?.();
    };

    const show = () => {
        previousFocus = document.activeElement;
        popup.hidden = false;
        popup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('telegram-popup-open');
        rememberShown();

        window.requestAnimationFrame(() => {
            popup.classList.add('is-visible');
            popup.querySelector('[data-telegram-popup-close]')?.focus();
        });

        if (autoCloseDelay > 0) {
            autoCloseTimer = window.setTimeout(close, autoCloseDelay);
        }
    };

    popup.querySelectorAll('[data-telegram-popup-close], [data-telegram-popup-subscribe]').forEach((control) => {
        control.addEventListener('click', close);
    });

    popup.addEventListener('click', (event) => {
        if (event.target === popup) close();
    });

    document.addEventListener('keydown', (event) => {
        if (!popup.hidden && event.key === 'Escape') close();
    });

    if (!wasShown()) {
        window.setTimeout(show, showDelay);
    }
}
