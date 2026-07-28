let csrfTokenRequest;

export async function getCsrfToken() {
    const existingToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    if (existingToken) {
        return existingToken;
    }

    if (!csrfTokenRequest) {
        const endpoint = document.querySelector('meta[name="csrf-endpoint"]')?.getAttribute('content')
            ?? '/csrf-token';

        csrfTokenRequest = fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.token) {
                    throw new Error('Не удалось подготовить безопасную отправку формы. Обновите страницу.');
                }

                const meta = document.querySelector('meta[name="csrf-token"]');

                if (meta) {
                    meta.setAttribute('content', payload.token);
                }

                return payload.token;
            })
            .catch((error) => {
                csrfTokenRequest = undefined;
                throw error;
            });
    }

    return csrfTokenRequest;
}

export async function postJson(url, body) {
    const csrfToken = await getCsrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = payload.errors ? Object.values(payload.errors).flat() : [];
        const message = payload.message ?? (errors.length ? errors.join(' ') : 'Не удалось отправить. Попробуйте позже.');

        throw new Error(message);
    }

    return payload;
}
