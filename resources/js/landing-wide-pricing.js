const formatPrice = new Intl.NumberFormat('ru-RU', {
    maximumFractionDigits: 0,
});

const initWidePricing = (calculator) => {
    if (calculator.dataset.widePricingReady === 'true') {
        return;
    }

    const usersSelect = calculator.querySelector('[data-wide-pricing-users]');
    const total = calculator.querySelector('[data-wide-pricing-total]');
    const usersNote = calculator.querySelector('[data-wide-pricing-users-note]');
    const suffix = calculator.querySelector('[data-wide-pricing-suffix]');
    const decreaseButton = calculator.querySelector('[data-wide-pricing-decrease]');
    const increaseButton = calculator.querySelector('[data-wide-pricing-increase]');
    const periodButtons = [...calculator.querySelectorAll('[data-wide-pricing-period-button]')];

    if (!usersSelect || !total) {
        return;
    }

    const options = [...calculator.querySelectorAll('[data-wide-pricing-option]')];
    const optionPriceLabels = [...calculator.querySelectorAll('[data-wide-pricing-option-price]')];
    const basePrice = Math.max(0, Number(calculator.dataset.basePrice) || 0);
    const minimumUsers = Math.max(1, Number(usersSelect.min) || 1);
    const maximumUsers = Math.max(minimumUsers, Number(usersSelect.max) || minimumUsers);
    let userCountLabels = {};
    let period = calculator.dataset.widePricingPeriod === 'year' ? 'year' : 'month';

    try {
        userCountLabels = JSON.parse(calculator.dataset.userCountLabels || '{}');
    } catch {
        userCountLabels = {};
    }

    const update = () => {
        const users = Math.min(maximumUsers, Math.max(minimumUsers, Number(usersSelect.value) || minimumUsers));
        usersSelect.value = String(users);
        decreaseButton?.toggleAttribute('disabled', users <= minimumUsers);
        increaseButton?.toggleAttribute('disabled', users >= maximumUsers);
        const optionsPrice = options.reduce(
            (sum, option) => sum + (option.checked ? Math.max(0, Number(option.value) || 0) : 0),
            0,
        );
        const periodMultiplier = period === 'year' ? 12 : 1;
        const periodSuffix = period === 'year'
            ? calculator.dataset.yearCurrencySuffix
            : calculator.dataset.monthCurrencySuffix;

        if (usersNote) {
            const lastTwoDigits = users % 100;
            const lastDigit = users % 10;
            const workplaceNoun = lastTwoDigits >= 11 && lastTwoDigits <= 14
                ? 'рабочих мест'
                : lastDigit === 1
                    ? 'рабочее место'
                    : lastDigit >= 2 && lastDigit <= 4
                        ? 'рабочих места'
                        : 'рабочих мест';

            usersNote.textContent = userCountLabels[users]
                || `за ${formatPrice.format(users)} ${workplaceNoun}`;
        }
        periodButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.period === period));
        });
        optionPriceLabels.forEach((label) => {
            label.textContent = formatPrice.format((Math.max(0, Number(label.dataset.monthlyPrice) || 0)) * periodMultiplier);
        });
        calculator.querySelectorAll('[data-wide-pricing-option-suffix]').forEach((node) => {
            node.textContent = periodSuffix || '';
        });
        if (suffix) {
            suffix.textContent = periodSuffix || '';
        }
        calculator.dataset.widePricingPeriod = period;
        total.textContent = formatPrice.format(((basePrice * users) + optionsPrice) * periodMultiplier);
    };

    decreaseButton?.addEventListener('click', () => {
        usersSelect.value = String((Number(usersSelect.value) || minimumUsers) - 1);
        update();
    });
    increaseButton?.addEventListener('click', () => {
        usersSelect.value = String((Number(usersSelect.value) || minimumUsers) + 1);
        update();
    });
    periodButtons.forEach((button) => button.addEventListener('click', () => {
        period = button.dataset.period === 'year' ? 'year' : 'month';
        update();
    }));
    options.forEach((option) => option.addEventListener('change', update));
    calculator.dataset.widePricingReady = 'true';
    update();
};

document.querySelectorAll('[data-wide-pricing]').forEach(initWidePricing);
