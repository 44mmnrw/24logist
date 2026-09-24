import { createRouletteOutcome, LOGISTRU_COMBINATION_WEIGHT, LOGISTRU_SYMBOL, makeOperators } from './logic.js';
import { createConfettiController } from '../epd-game/confetti.js';

const delay = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
const SPIN_DURATION_MULTIPLIER = 3;
const formatAttemptCounter = (attempts) => String(Math.max(0, Math.trunc(attempts))).padStart(5, '0');

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

    if (!['kontur', 'astral', 'saby', 'ediveb', 'evotor', 'taxcom', 'logistru'].includes(operator.logo)) {
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
    const audio = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    let busy = false;
    let displayedAttempts = null;
    let displayedJackpots = null;

    const renderAttempts = (attempts) => {
        displayedAttempts = attempts;
        attemptCount.textContent = formatAttemptCounter(attempts);
    };

    const renderJackpots = (jackpots) => {
        displayedJackpots = jackpots;
        jackpotCount.textContent = String(Math.max(0, Math.trunc(jackpots)));
    };

    const requestAttempts = async (url, options = {}) => {
        const response = await window.fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            ...options,
        });
        if (!response.ok) throw new Error(`Attempt counter request failed: ${response.status}`);
        const payload = await response.json();
        renderAttempts(Number(payload.attempts));
        renderJackpots(Number(payload.jackpots));
        if (playerAttemptCount && Number.isFinite(Number(payload.player_attempts))) {
            playerAttemptCount.textContent = new Intl.NumberFormat('ru-RU').format(Number(payload.player_attempts));
        }
    };

    const incrementAttempts = (isJackpot) => {
        if (displayedAttempts !== null) renderAttempts(displayedAttempts + 1);
        if (isJackpot && displayedJackpots !== null) renderJackpots(displayedJackpots + 1);
        requestAttempts(game.dataset.attemptsIncrementUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ jackpot: isJackpot }),
        }).catch(() => {});
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
        const outcome = createRouletteOutcome(operators);
        incrementAttempts(outcome.jackpot);
        game.dataset.phase = 'spinning';
        game.dataset.outcome = '';
        game.dataset.longResult = 'false';
        spinButton.disabled = true;
        spinLabel.textContent = 'Крутим барабаны…';
        lever.disabled = true;
        reelsPanel.setAttribute('aria-busy', 'true');
        status.textContent = 'Барабаны вращаются…';
        result.textContent = '';
        confetti.stop();
        play('pull');

        await Promise.all(reels.map((reel, index) => spinReel(reel, index, outcome.reels[index])));

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
