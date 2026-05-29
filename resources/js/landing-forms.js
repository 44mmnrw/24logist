export function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
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
