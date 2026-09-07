const form = document.querySelector('[data-platform-handoff-form]');

if (form instanceof HTMLFormElement) {
    window.requestAnimationFrame(() => form.requestSubmit());
}

export {};
