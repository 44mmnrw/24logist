const initializeGrowthDashboard = (dashboard) => {
    if (dashboard.dataset.growthInitialized === 'true') {
        return;
    }

    dashboard.dataset.growthInitialized = 'true';

    const unitButtons = [...dashboard.querySelectorAll('[data-growth-unit]')];
    const segmentValues = [...dashboard.querySelectorAll('[data-growth-percent][data-growth-count]')];
    const total = dashboard.querySelector('[data-growth-total]');
    const totalLabel = dashboard.querySelector('[data-growth-total-label]');

    unitButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const showCounts = button.dataset.growthUnit === 'count';

            unitButtons.forEach((item) => {
                const active = item === button;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-pressed', String(active));
            });

            segmentValues.forEach((value) => {
                value.textContent = showCounts ? value.dataset.growthCount : value.dataset.growthPercent;
            });

            total.textContent = showCounts ? '59' : '100%';
            totalLabel.textContent = showCounts ? 'Заявок' : 'Доля';
        });
    });

    const tabButtons = [...dashboard.querySelectorAll('[data-growth-view]')];
    const customerList = dashboard.querySelector('[data-growth-customer-list]');
    const dataElement = dashboard.querySelector('[data-growth-customer-data]');

    if (!customerList || !dataElement) {
        return;
    }

    let customerViews;

    try {
        customerViews = JSON.parse(dataElement.textContent);
    } catch {
        return;
    }

    const renderCustomerView = (view) => {
        const customers = customerViews[view];
        const rows = [...customerList.querySelectorAll('.growth-customer')];

        if (!Array.isArray(customers) || customers.length !== rows.length) {
            return;
        }

        dashboard.classList.add('is-updating');

        rows.forEach((row, index) => {
            const customer = customers[index];
            row.querySelector('.growth-customer__name').textContent = customer.name;
            row.querySelector('.growth-customer__track span').style.width = '0%';
            row.querySelector(':scope > strong').textContent = customer.value;
        });

        requestAnimationFrame(() => {
            rows.forEach((row, index) => {
                row.querySelector('.growth-customer__track span').style.width = `${customers[index].width}%`;
            });
            dashboard.classList.remove('is-updating');
        });
    };

    tabButtons.forEach((button, buttonIndex) => {
        button.addEventListener('click', () => {
            tabButtons.forEach((item) => {
                const active = item === button;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', String(active));
                item.tabIndex = active ? 0 : -1;
            });

            renderCustomerView(button.dataset.growthView);
        });

        button.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (buttonIndex + direction + tabButtons.length) % tabButtons.length;
            tabButtons[nextIndex].focus();
            tabButtons[nextIndex].click();
        });
    });
};

document.querySelectorAll('[data-growth-dashboard]').forEach(initializeGrowthDashboard);

