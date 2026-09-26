import { LOGISTRU_COMBINATION_WEIGHT, LOGISTRU_SYMBOL, makeOperators } from './logic.js';
import { createConfettiController } from '../epd-game/confetti.js';
import { createSmartCaptcha } from '../smartcaptcha.js';

const delay = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
const SPIN_DURATION_MULTIPLIER = 3;
const formatAttemptCounter = (attempts) => String(Math.max(0, Math.trunc(attempts))).padStart(5, '0');
const newRequestId = () => {
    if (typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
    const bytes = window.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
};

const makeSymbol = (operator) => {
    const symbol = document.createElement('div');
    symbol.className = 'etrn-roulette__symbol';
    symbol.title = operator.name;

    if (operator.logo) {
        const logo = document.createElement('span');
        logo.className = `etrn-roulette__symbol-logo etrn-roulette__symbol-logo--${operator.logo}`;
        logo.setAttribute('aria-hidden', 'true');
        symbol.append(logo);

        if (operator.id === LOGISTRU_SYMBOL.id) {
            const badge = document.createElement('span');
            badge.className = 'etrn-roulette__symbol-bonus';
            badge.textContent = 'БОНУС';
            symbol.append(badge);
        }
    }

    if (!['kontur', 'astral', 'saby', 'ediveb', 'evotor', 'taxcom', 'sber', 'tochka', 'mig', 'itcom', 'stek', 'oneofd', 'atidoki', 'niias', 'crpt', 'logistru'].includes(operator.logo)) {
        const name = document.createElement('span');
        name.className = operator.logo
            ? 'etrn-roulette__symbol-name'
            : 'etrn-roulette__symbol-text';
        if (operator.shortName.length > 10) name.classList.add('is-long');
        name.textContent = operator.shortName;
        symbol.append(name);
    }
    return symbol;
};

export const createEtrnRoulette = (game) => {
    const operators = makeOperators(JSON.parse(game.querySelector('[data-etrn-operators]').textContent));
    const symbols = [
        ...operators,
        ...Array(LOGISTRU_COMBINATION_WEIGHT).fill(LOGISTRU_SYMBOL),
    ];
    const reels = [...game.querySelectorAll('[data-etrn-reel]')];
    const reelsPanel = game.querySelector('[data-etrn-reels]');
    const spinButton = game.querySelector('[data-etrn-spin]');
    const spinLabel = game.querySelector('[data-etrn-spin-label]');
    const lever = game.querySelector('[data-etrn-lever]');
    const status = game.querySelector('[data-etrn-status]');
    const result = game.querySelector('[data-etrn-result]');
    const attemptCount = game.querySelector('[data-etrn-attempt-count]');
    const jackpotCount = game.querySelector('[data-etrn-jackpot-count]');
    const playerAttemptCount = game.querySelector('[data-etrn-player-attempt-count]');
    const authDialog = game.querySelector('[data-etrn-auth-dialog]');
    const authOpen = game.querySelector('[data-etrn-auth-open]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const confetti = createConfettiController({ game, reducedMotion });
    const captcha = createSmartCaptcha(game);
    const audio = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    let busy = false;
    let pendingRequestId = null;

    const renderAttempts = (attempts) => {
        attemptCount.textContent = formatAttemptCounter(attempts);
    };

    const renderJackpots = (jackpots) => {
        jackpotCount.textContent = String(Math.max(0, Math.trunc(jackpots)));
    };

    const applyCounters = (payload) => {
        renderAttempts(Number(payload.attempts));
        renderJackpots(Number(payload.jackpots));
        if (playerAttemptCount && Number.isFinite(Number(payload.player_attempts))) {
            playerAttemptCount.textContent = new Intl.NumberFormat('ru-RU').format(Number(payload.player_attempts));
        }
    };

    const requestAttempts = async (url, options = {}, updateCounter = true) => {
        const response = await window.fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            ...options,
        });
        if (!response.ok) {
            const errorPayload = await response.json().catch(() => ({}));
            const error = new Error(errorPayload.message || `Attempt request failed: ${response.status}`);
            error.status = response.status;
            throw error;
        }
        const payload = await response.json();
        if (updateCounter) applyCounters(payload);
        return payload;
    };

    const requestSpin = async () => {
        pendingRequestId ??= newRequestId();
        const smartToken = await captcha.getToken();
        let payload;
        try {
            payload = await requestAttempts(game.dataset.attemptsIncrementUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ request_id: pendingRequestId, smart_token: smartToken }),
            }, false);
        } finally {
            captcha.reset();
        }
        const byId = new Map([...operators, LOGISTRU_SYMBOL].map((operator) => [operator.id, operator]));
        const reelIds = payload.outcome?.reels;
        if (!Array.isArray(reelIds) || reelIds.length !== reels.length || reelIds.some((id) => !byId.has(id))) {
            throw new Error('Некорректный ответ сервера. Обновите страницу.');
        }
        pendingRequestId = null;
        return {
            ...payload.outcome,
            counters: payload,
            reels: reelIds.map((id) => byId.get(id)),
            destination: payload.outcome.destination ? byId.get(payload.outcome.destination) : null,
        };
    };

    const play = (type) => {
        const sound = audio[type].cloneNode();
        sound.volume = type === 'pull' ? 0.28 : 0.36;
        sound.play().catch(() => {});
    };

    const renderReel = (reel, operator) => {
        const track = reel.querySelector('[data-etrn-track]');
        track.replaceChildren(makeSymbol(operator));
        track.style.transform = 'translateY(0)';
        reel.dataset.operator = operator.id;
        reel.setAttribute('aria-label', operator.name);
    };

    const spinReel = async (reel, index, finalOperator) => {
        if (reducedMotion.matches) {
            await delay(80 + index * 60);
            renderReel(reel, finalOperator);
            play('stop');
            return;
        }

        const track = reel.querySelector('[data-etrn-track]');
        const current = symbols.find(({ id }) => id === reel.dataset.operator) || operators[index];
        const length = (18 + index * 4) * SPIN_DURATION_MULTIPLIER;
        const sequence = [current];
        for (let step = 1; step < length - 1; step += 1) {
            const previous = sequence[step - 1];
            const choices = symbols.filter(({ id }) => id !== previous.id);
            sequence.push(choices[Math.floor(Math.random() * choices.length)]);
        }
        sequence.push(finalOperator);
        track.replaceChildren(...sequence.map(makeSymbol));

        const distance = (length - 1) * reel.getBoundingClientRect().height;
        const duration = (1700 + index * 420) * SPIN_DURATION_MULTIPLIER;
        if (typeof track.animate === 'function') {
            const animation = track.animate([
                { transform: 'translateY(0)' },
                { transform: `translateY(-${distance}px)` },
            ], { duration, easing: 'cubic-bezier(.12,.68,.18,1)', fill: 'forwards' });
            await animation.finished.catch(() => {});
            animation.cancel();
        } else {
            track.style.transition = `transform ${duration}ms cubic-bezier(.12,.68,.18,1)`;
            track.style.transform = `translateY(-${distance}px)`;
            await delay(duration);
            track.style.transition = 'none';
        }
        renderReel(reel, finalOperator);
        play('stop');
    };

    const spin = async () => {
        if (busy) return;
        busy = true;
        game.dataset.phase = 'spinning';
        game.dataset.outcome = '';
        game.dataset.longResult = 'false';
        spinButton.disabled = true;
        spinLabel.textContent = 'Крутим барабаны…';
        lever.disabled = true;
        reelsPanel.setAttribute('aria-busy', 'true');
        status.textContent = 'Проверяем запуск…';
        result.textContent = '';
        confetti.stop();
        let outcome;
        try {
            outcome = await requestSpin();
        } catch (error) {
            captcha.reset();
            if (error.status === 409) pendingRequestId = null;
            game.dataset.phase = 'idle';
            reelsPanel.setAttribute('aria-busy', 'false');
            status.textContent = error.message || 'Не удалось запустить барабаны. Попробуйте ещё раз.';
            spinLabel.textContent = 'Крутить ещё раз';
            spinButton.disabled = false;
            lever.disabled = false;
            busy = false;
            return;
        }
        status.textContent = 'Барабаны вращаются…';
        play('pull');

        await Promise.all(reels.map((reel, index) => spinReel(reel, index, outcome.reels[index])));

        applyCounters(outcome.counters);

        game.dataset.phase = 'stopped';
        game.dataset.outcome = outcome.jackpot ? 'jackpot' : outcome.matched ? 'match' : 'miss';
        game.dataset.longResult = String(outcome.destination && outcome.destination.name.length > 26);
        reelsPanel.setAttribute('aria-busy', 'false');
        status.textContent = outcome.jackpot ? 'Три бонуса ЛогистРу!' : outcome.matched ? 'ЭТрН отправляется в' : 'Без выигрыша';
        result.textContent = outcome.jackpot
            ? 'Супербонус'
            : outcome.matched
            ? outcome.destination.name
            : 'Попробуйте ещё раз';
        spinLabel.textContent = 'Крутить ещё раз';
        if (outcome.jackpot) confetti.start();
        play(outcome.matched ? 'success' : 'failure');
        spinButton.disabled = false;
        lever.disabled = false;
        busy = false;
    };

    reels.forEach((reel, index) => renderReel(reel, index === 2 ? LOGISTRU_SYMBOL : operators[index]));
    captcha.load();
    requestAttempts(game.dataset.attemptsUrl).catch(() => {});
    if (authDialog && authOpen) {
        const closeAuthDialog = () => {
            if (typeof authDialog.close === 'function') authDialog.close();
            else authDialog.removeAttribute('open');
        };

        authOpen.addEventListener('click', (event) => {
            event.preventDefault();
            if (typeof authDialog.showModal === 'function') authDialog.showModal();
            else authDialog.setAttribute('open', '');
        });
        authDialog.querySelector('[data-etrn-auth-close]')?.addEventListener('click', closeAuthDialog);
        authDialog.addEventListener('click', (event) => {
            if (event.target === authDialog) closeAuthDialog();
        });
    }
    spinButton.addEventListener('click', spin);
    lever.addEventListener('click', spin);
    return { spin };
};
