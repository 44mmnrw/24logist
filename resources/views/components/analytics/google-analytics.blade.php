@php
    $measurementId = app(\App\Services\SiteSettingsService::class)->googleAnalyticsMeasurementId();
@endphp

@if ($measurementId)
    <script>
        (() => {
            const endpoint = 'https://a.24logist.ru/';
            const consentCookie = 'logistru_cookie_notice=acknowledged';
            const clientStorageKey = 'logistru_analytics_client';
            const sessionStorageKey = 'logistru_analytics_session';
            let started = false;
            let clientId = '';
            let sessionId = 0;
            let scrollSent = false;

            const hasConsent = () => document.cookie
                .split(';')
                .some((cookie) => cookie.trim() === consentCookie);

            const positiveInteger = () => {
                if (window.crypto?.getRandomValues) {
                    const value = new Uint32Array(1);
                    window.crypto.getRandomValues(value);

                    return Math.max(1, value[0]);
                }

                return Math.floor(Math.random() * 4294967295) + 1;
            };

            const readOrCreate = (storage, key, factory) => {
                try {
                    const existing = storage.getItem(key);

                    if (existing) {
                        return existing;
                    }

                    const value = factory();
                    storage.setItem(key, value);

                    return value;
                } catch {
                    return factory();
                }
            };

            const cleanUrl = (value) => {
                try {
                    const url = new URL(value, window.location.origin);

                    return `${url.protocol}//${url.host}${url.pathname}`;
                } catch {
                    return '';
                }
            };

            const deliver = (body) => {
                const blob = new Blob([body], { type: 'text/plain;charset=UTF-8' });

                if (navigator.sendBeacon?.(endpoint, blob)) {
                    return;
                }

                fetch(endpoint, {
                    method: 'POST',
                    mode: 'cors',
                    credentials: 'omit',
                    keepalive: true,
                    headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
                    body,
                }).catch(() => {});
            };

            const send = (name, params = {}) => {
                if (!started || !clientId || !sessionId) {
                    return;
                }

                deliver(JSON.stringify({
                    consent: true,
                    client_id: clientId,
                    session_id: sessionId,
                    events: [{ name, params }],
                }));
            };

            const start = () => {
                if (started) {
                    return;
                }

                started = true;
                clientId = readOrCreate(
                    window.localStorage,
                    clientStorageKey,
                    () => `${positiveInteger()}.${Math.floor(Date.now() / 1000)}`,
                );
                sessionId = Number(readOrCreate(
                    window.sessionStorage,
                    sessionStorageKey,
                    () => String(Math.floor(Date.now() / 1000)),
                ));

                send('page_view', {
                    page_location: cleanUrl(window.location.href),
                    page_referrer: cleanUrl(document.referrer),
                    page_title: document.title,
                    engagement_time_msec: 1,
                });

                document.addEventListener('click', (event) => {
                    const target = event.target instanceof Element
                        ? event.target.closest('a[href], button')
                        : null;

                    if (!target) {
                        return;
                    }

                    send('click', {
                        link_url: target instanceof HTMLAnchorElement ? cleanUrl(target.href) : '',
                        link_text: (target.textContent || target.getAttribute('aria-label') || '')
                            .trim()
                            .slice(0, 160),
                    });
                }, { capture: true });

                const startedForms = new WeakSet();
                document.addEventListener('focusin', (event) => {
                    const form = event.target instanceof Element ? event.target.closest('form') : null;

                    if (!form || startedForms.has(form)) {
                        return;
                    }

                    startedForms.add(form);
                    send('form_start', {
                        form_id: form.id,
                        form_name: form.getAttribute('name') || '',
                    });
                });

                document.addEventListener('submit', (event) => {
                    const form = event.target instanceof HTMLFormElement ? event.target : null;

                    if (!form) {
                        return;
                    }

                    send('form_submit', {
                        form_id: form.id,
                        form_name: form.getAttribute('name') || '',
                    });
                }, { capture: true });

                window.addEventListener('scroll', () => {
                    if (scrollSent) {
                        return;
                    }

                    const available = document.documentElement.scrollHeight - window.innerHeight;
                    if (available > 0 && window.scrollY / available >= 0.9) {
                        scrollSent = true;
                        send('scroll', { engagement_time_msec: 1 });
                    }
                }, { passive: true });
            };

            const initialise = () => {
                if (hasConsent()) {
                    start();

                    return;
                }

                document.querySelector('[data-cookie-accept]')?.addEventListener('click', start, { once: true });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialise, { once: true });
            } else {
                initialise();
            }
        })();
    </script>
@endif
