import { createRouletteOutcome, LOGISTRU_SYMBOL, makeOperators } from './logic.js';

const delay = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

const makeSymbol = (operator) => {
    const symbol = document.createElement('div');
    symbol.className = 'etrn-roulette__symbol';
    symbol.title = operator.name;

    if (operator.logo) {
        const logo = document.createElement('span');
        logo.className = `etrn-roulette__symbol-logo etrn-roulette__symbol-logo--${operator.logo}`;
        logo.setAttribute('aria-hidden', 'true');
        symbol.append(logo);
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
    const symbols = [...operators, LOGISTRU_SYMBOL];
    const reels = [...game.querySelectorAll('[data-etrn-reel]')];
    const reelsPanel = game.querySelector('[data-etrn-reels]');
    const spinButton = game.querySelector('[data-etrn-spin]');
    const spinLabel = game.querySelector('[data-etrn-spin-label]');
    const lever = game.querySelector('[data-etrn-lever]');
    const status = game.querySelector('[data-etrn-status]');
    const result = game.querySelector('[data-etrn-result]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const audio = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    let busy = false;

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
        const length = 18 + index * 4;
        const sequence = [current];
        for (let step = 1; step < length - 1; step += 1) {
            const previous = sequence[step - 1];
            const choices = symbols.filter(({ id }) => id !== previous.id);
            sequence.push(choices[Math.floor(Math.random() * choices.length)]);
        }
        sequence.push(finalOperator);
        track.replaceChildren(...sequence.map(makeSymbol));

        const distance = (length - 1) * reel.getBoundingClientRect().height;
        const duration = 1700 + index * 420;
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
        status.textContent = 'Барабаны вращаются…';
        result.textContent = '';
        play('pull');

        const outcome = createRouletteOutcome(operators);
        await Promise.all(reels.map((reel, index) => spinReel(reel, index, outcome.reels[index])));

        game.dataset.phase = 'stopped';
        game.dataset.outcome = outcome.bonus ? 'bonus' : outcome.matched ? 'match' : 'miss';
        game.dataset.longResult = String(outcome.matched && outcome.destination.name.length > 26);
        reelsPanel.setAttribute('aria-busy', 'false');
        status.textContent = outcome.bonus ? 'Бонус ЛогистРу!' : outcome.matched ? 'Три оператора совпали!' : 'Без выигрыша';
        result.textContent = outcome.matched
            ? outcome.destination.name
            : 'Попробуйте ещё раз';
        spinLabel.textContent = 'Крутить ещё раз';
        play(outcome.matched ? 'success' : 'failure');
        spinButton.disabled = false;
        lever.disabled = false;
        busy = false;
    };

    reels.forEach((reel, index) => renderReel(reel, index === 2 ? LOGISTRU_SYMBOL : operators[index]));
    spinButton.addEventListener('click', spin);
    lever.addEventListener('click', spin);
    return { spin };
};
