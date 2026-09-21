const formatPrice = new Intl.NumberFormat('ru-RU', {
    maximumFractionDigits: 0,
});

const initWidePricing = (calculator) => {
    if (calculator.dataset.widePricingReady === 'true') {
        return;
    }

    const usersSelect = calculator.querySelector('[data-wide-pricing-users]');
    const total = calculator.querySelector('[data-wide-pricing-total]');
    const decreaseButton = calculator.querySelector('[data-wide-pricing-decrease]');
    const increaseButton = calculator.querySelector('[data-wide-pricing-increase]');

    if (!usersSelect || !total) {
        return;
    }

    const options = [...calculator.querySelectorAll('[data-wide-pricing-option]')];
    const basePrice = Math.max(0, Number(calculator.dataset.basePrice) || 0);
    const minimumUsers = Math.max(1, Number(usersSelect.min) || 1);
    const maximumUsers = Math.max(minimumUsers, Number(usersSelect.max) || minimumUsers);

    const update = () => {
        const users = Math.min(maximumUsers, Math.max(minimumUsers, Number(usersSelect.value) || minimumUsers));
        usersSelect.value = String(users);
        decreaseButton?.toggleAttribute('disabled', users <= minimumUsers);
        increaseButton?.toggleAttribute('disabled', users >= maximumUsers);
        const optionsPrice = options.reduce(
            (sum, option) => sum + (option.checked ? Math.max(0, Number(option.value) || 0) : 0),
            0,
        );

        total.textContent = formatPrice.format((basePrice * users) + optionsPrice);
    };

    decreaseButton?.addEventListener('click', () => {
        usersSelect.value = String((Number(usersSelect.value) || minimumUsers) - 1);
        update();
    });
    increaseButton?.addEventListener('click', () => {
        usersSelect.value = String((Number(usersSelect.value) || minimumUsers) + 1);
        update();
    });
    options.forEach((option) => option.addEventListener('change', update));
    calculator.dataset.widePricingReady = 'true';
    update();
};

document.querySelectorAll('[data-wide-pricing]').forEach(initWidePricing);
