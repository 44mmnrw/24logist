const popup = document.querySelector('[data-telegram-popup]');

if (popup) {
    const shownStorageKey = 'telegram-popup-last-shown';
    const suppressedSessionKey = 'telegram-popup-suppressed';
    const configuredDelay = Math.max(45, Number(popup.dataset.showDelay) || 45) * 1000;
    const configuredScrollPercent = Math.min(90, Math.max(25, Number(popup.dataset.scrollPercent) || 55));
    const autoCloseDelay = Math.max(0, Number(popup.dataset.autoCloseDelay) || 0) * 1000;
    const cooldownDays = Math.max(1, Number(popup.dataset.cooldownDays) || 7);
    const cooldownMs = cooldownDays * 24 * 60 * 60 * 1000;
    const leadSelector = '[data-landing-quiz], [data-landing-contact-form]';
    const subscribeLink = popup.querySelector('[data-telegram-popup-subscribe]');
    const isMobileDevice = window.navigator.userAgentData?.mobile === true
        || /Android|iPhone|iPad|iPod|Mobile/i.test(window.navigator.userAgent)
        || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    const showDelay = isMobileDevice ? Math.max(configuredDelay, 60_000) : configuredDelay;
    const scrollPercent = isMobileDevice ? Math.max(configuredScrollPercent, 65) : configuredScrollPercent;
    let showTimer;
    let autoCloseTimer;
    let isSuppressed = false;

    if (isMobileDevice && subscribeLink?.dataset.mobileUrl?.startsWith('tg://resolve?domain=')) {
        subscribeLink.href = subscribeLink.dataset.mobileUrl;
        subscribeLink.removeAttribute('target');
    }

    const readStorage = (storage, key) => {
        try {
            return storage.getItem(key);
        } catch {
            return null;
        }
    };

    const writeStorage = (storage, key, value) => {
        try {
            storage.setItem(key, value);
        } catch {
            // Storage can be unavailable in strict privacy modes.
        }
    };

    const wasShownRecently = () => {
        const timestamp = Number(readStorage(window.localStorage, shownStorageKey));

        return Number.isFinite(timestamp) && timestamp > 0 && Date.now() - timestamp < cooldownMs;
    };

    const wasSuppressed = () => readStorage(window.sessionStorage, suppressedSessionKey) === '1';

    const cleanupTriggers = () => {
        window.clearTimeout(showTimer);
        window.removeEventListener('scroll', onScroll);
        document.removeEventListener('pointerdown', onLeadInteraction, true);
        document.removeEventListener('focusin', onLeadInteraction, true);
    };

    const close = () => {
        window.clearTimeout(autoCloseTimer);
        popup.classList.remove('is-visible');
        popup.setAttribute('aria-hidden', 'true');

        window.setTimeout(() => {
            popup.hidden = true;
        }, 200);
    };

    const show = () => {
        if (
            isSuppressed
            || wasSuppressed()
            || wasShownRecently()
            || document.visibilityState !== 'visible'
        ) {
            return;
        }

        popup.hidden = false;
        popup.setAttribute('aria-hidden', 'false');
        writeStorage(window.localStorage, shownStorageKey, String(Date.now()));
        cleanupTriggers();

        window.requestAnimationFrame(() => {
            popup.classList.add('is-visible');
        });

        if (autoCloseDelay > 0) {
            autoCloseTimer = window.setTimeout(close, autoCloseDelay);
        }
    };

    function onScroll() {
        const documentHeight = document.documentElement.scrollHeight;

        if (documentHeight <= window.innerHeight) {
            return;
        }

        const viewedPercent = ((window.scrollY + window.innerHeight) / documentHeight) * 100;

        if (viewedPercent >= scrollPercent) {
            show();
        }
    }

    function onLeadInteraction(event) {
        if (!(event.target instanceof Element) || !event.target.closest(leadSelector)) {
            return;
        }

        isSuppressed = true;
        writeStorage(window.sessionStorage, suppressedSessionKey, '1');
        cleanupTriggers();
    }

    popup.querySelectorAll('[data-telegram-popup-close], [data-telegram-popup-subscribe]').forEach((control) => {
        control.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (!popup.hidden && event.key === 'Escape') {
            close();
        }
    });

    if (!wasShownRecently() && !wasSuppressed()) {
        window.addEventListener('scroll', onScroll, { passive: true });
        document.addEventListener('pointerdown', onLeadInteraction, true);
        document.addEventListener('focusin', onLeadInteraction, true);
        showTimer = window.setTimeout(show, showDelay);
        onScroll();
    }
}
