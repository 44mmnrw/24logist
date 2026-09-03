import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const numberFormatter = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 });
const decimalFormatter = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 });

const parseNumber = (value) => {
    const parsed = Number.parseFloat(String(value ?? '').replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : null;
};

const normalizePoints = (points) => (Array.isArray(points) ? points : [])
    .map((point) => {
        if (!Array.isArray(point) || point.length < 2) return null;
        const lat = parseNumber(point[0]);
        const lng = parseNumber(point[1]);
        return lat === null || lng === null ? null : [lat, lng];
    })
    .filter(Boolean);

const initCitySuggest = (root, role, url) => {
    const input = root.querySelector(`[data-route-city="${role}"]`);
    const list = root.querySelector(`[data-route-suggest="${role}"]`);
    if (!(input instanceof HTMLInputElement) || !(list instanceof HTMLElement)) return;

    let timer = null;
    let requestVersion = 0;

    const hide = () => {
        list.hidden = true;
        list.replaceChildren();
    };

    const render = (items) => {
        list.replaceChildren();
        const values = (Array.isArray(items) ? items : [])
            .map((item) => String(item ?? '').trim())
            .filter(Boolean);

        values.forEach((value) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = value;
            button.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                input.value = value;
                hide();
            });
            list.append(button);
        });
        list.hidden = values.length === 0;
    };

    input.addEventListener('input', () => {
        if (timer !== null) window.clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) {
            hide();
            return;
        }

        const version = ++requestVersion;
        timer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${url}?${new URLSearchParams({ query })}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok || version !== requestVersion) return hide();
                const payload = await response.json();
                render(payload?.suggestions);
            } catch {
                hide();
            }
        }, 250);
    });

    input.addEventListener('blur', () => window.setTimeout(hide, 120));
};

const initRouteCalculator = (root) => {
    if (!(root instanceof HTMLElement) || root.dataset.initialized === '1') return;
    root.dataset.initialized = '1';

    const mapNode = root.querySelector('[data-route-map]');
    const form = root.querySelector('[data-route-form]');
    const status = root.querySelector('[data-route-status]');
    const result = root.querySelector('[data-route-result]');
    const submit = root.querySelector('[data-route-submit]');
    const profile = root.querySelector('[data-route-profile]');
    const truckFields = root.querySelector('[data-route-truck]');
    const suggestUrl = String(root.dataset.citySuggestUrl ?? '');
    const calculateUrl = String(root.dataset.calculateUrl ?? '');

    if (!(mapNode instanceof HTMLElement) || !(form instanceof HTMLFormElement)) return;

    const map = L.map(mapNode, {
        zoomControl: true,
        scrollWheelZoom: true,
        attributionControl: false,
    }).setView([56.2, 47.2], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);
    const routeLayer = L.layerGroup().addTo(map);

    const showStatus = (message = '', error = false) => {
        if (!(status instanceof HTMLElement)) return;
        status.textContent = message;
        status.hidden = message === '';
        status.classList.toggle('is-error', error);
    };

    const setResultRow = (name, value) => {
        const row = result?.querySelector(`[data-result-row="${name}"]`);
        const node = result?.querySelector(`[data-result-value="${name}"]`);
        if (!(row instanceof HTMLElement) || !(node instanceof HTMLElement)) return;
        row.hidden = value === null;
        node.textContent = value ?? '';
    };

    const clearResult = () => {
        routeLayer.clearLayers();
        map.setView([56.2, 47.2], 5);
        if (result instanceof HTMLElement) result.hidden = true;
        showStatus();
    };

    const syncTruckFields = () => {
        if (truckFields instanceof HTMLFieldSetElement) {
            truckFields.hidden = !(profile instanceof HTMLSelectElement) || profile.value !== 'truck';
        }
    };
    profile?.addEventListener('change', syncTruckFields);
    syncTruckFields();

    initCitySuggest(root, 'from', suggestUrl);
    initCitySuggest(root, 'to', suggestUrl);

    form.addEventListener('reset', () => window.setTimeout(() => {
        syncTruckFields();
        clearResult();
    }));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity() || calculateUrl === '') return;

        const data = new FormData(form);
        const payload = {};
        ['from_city', 'to_city', 'price_per_km', 'driver_work_hours_per_day', 'max_km_per_day', 'toll_mode', 'routing_profile'].forEach((key) => {
            const value = String(data.get(key) ?? '').trim();
            if (value !== '') payload[key] = value;
        });

        if (payload.routing_profile === 'truck') {
            const truck = {};
            ['gross_weight_t', 'max_axle_load_t', 'height_m', 'width_m', 'length_m', 'axle_count'].forEach((key) => {
                const value = String(data.get(`truck[${key}]`) ?? '').trim();
                if (value !== '') truck[key] = value;
            });
            if (data.get('truck[hazmat]') !== null) truck.hazmat = true;
            if (Object.keys(truck).length > 0) payload.truck = truck;
        }

        showStatus('Строим маршрут…');
        if (result instanceof HTMLElement) result.hidden = true;
        if (submit instanceof HTMLButtonElement) submit.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const response = await fetch(calculateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify(payload),
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok) {
                const fallback = response.status === 429
                    ? 'Слишком много запросов. Подождите минуту.'
                    : 'Не удалось рассчитать маршрут.';
                throw new Error(String(json?.message || fallback));
            }

            const points = normalizePoints(json?.route_points);
            if (points.length < 2) throw new Error('Платформа не вернула линию маршрута.');

            routeLayer.clearLayers();
            const line = L.polyline(points, { color: '#2257d6', weight: 5, opacity: 0.78 }).addTo(routeLayer);
            L.circleMarker(points[0], { radius: 7, color: '#fff', weight: 3, fillColor: '#16a34a', fillOpacity: 1 }).addTo(routeLayer);
            L.circleMarker(points[points.length - 1], { radius: 7, color: '#fff', weight: 3, fillColor: '#dc2626', fillOpacity: 1 }).addTo(routeLayer);
            map.fitBounds(line.getBounds(), { padding: [32, 32] });

            const distance = parseNumber(json?.distance_km);
            const hours = parseNumber(json?.travel_time?.total_work_hours);
            const days = Number.parseInt(String(json?.travel_time?.total_days ?? ''), 10);
            const freight = parseNumber(json?.freight_total_price);
            const toll = parseNumber(json?.toll_distance_km);
            const total = parseNumber(json?.total_price);

            setResultRow('distance', distance === null ? null : `${numberFormatter.format(distance)} км`);
            setResultRow('time', hours === null ? null : `${decimalFormatter.format(hours)} ч${Number.isFinite(days) && days > 0 ? ` · ${days} дн.` : ''}`);
            setResultRow('freight', freight === null || payload.price_per_km === undefined ? null : `${numberFormatter.format(freight)} ₽`);
            setResultRow('toll', toll === null || toll <= 0 ? null : `${decimalFormatter.format(toll)} км`);
            setResultRow('total', total === null || payload.price_per_km === undefined ? null : `${numberFormatter.format(total)} ₽`);

            const warnings = (Array.isArray(json?.routing_warnings) ? json.routing_warnings : [])
                .map((warning) => String(warning ?? '').trim())
                .filter(Boolean);
            const warningsBox = result?.querySelector('[data-route-warnings]');
            const warningsList = warningsBox?.querySelector('ul');
            if (warningsBox instanceof HTMLElement && warningsList instanceof HTMLUListElement) {
                warningsList.replaceChildren(...warnings.map((warning) => {
                    const item = document.createElement('li');
                    item.textContent = warning;
                    return item;
                }));
                warningsBox.hidden = warnings.length === 0;
            }

            if (result instanceof HTMLElement) result.hidden = false;
            showStatus('Маршрут рассчитан.');
            window.setTimeout(() => map.invalidateSize(), 0);
        } catch (error) {
            showStatus(error instanceof Error ? error.message : 'Не удалось рассчитать маршрут.', true);
        } finally {
            if (submit instanceof HTMLButtonElement) submit.disabled = false;
        }
    });

    window.setTimeout(() => map.invalidateSize(), 0);
};

document.querySelectorAll('[data-route-calculator-calculator]').forEach(initRouteCalculator);


