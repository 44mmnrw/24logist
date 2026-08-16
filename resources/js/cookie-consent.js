const COOKIE_NAME = 'logistru_cookie_notice';
const COOKIE_VALUE = 'acknowledged';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

const hasAcknowledgement = () => document.cookie
    .split(';')
    .some((cookie) => cookie.trim() === `${COOKIE_NAME}=${COOKIE_VALUE}`);

const initialiseCookieNotice = () => {
    const notice = document.querySelector('[data-cookie-consent]');

    if (!notice || hasAcknowledgement()) {
        return;
    }

    notice.hidden = false;

    notice.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';

        document.cookie = `${COOKIE_NAME}=${COOKIE_VALUE}; Path=/; Max-Age=${COOKIE_MAX_AGE}; SameSite=Lax${secure}`;
        notice.hidden = true;
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseCookieNotice, { once: true });
} else {
    initialiseCookieNotice();
}
