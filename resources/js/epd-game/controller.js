import { canStartRound, createRoundOutcome, getSelectionPhase } from './logic.js';
import { createGameMedia } from './media.js';
import { createReelController } from './reels.js';

const wait = (duration) => new Promise((resolve) => window.setTimeout(resolve, duration));

const COPY = {
    idle: {
        core: 'Выберите операторов',
        kicker: 'Ожидание запуска',
        status: 'Связь не проверена',
        reels: 'Барабаны готовы к передаче',
    },
    ready: {
        core: 'Готово к проверке',
        kicker: 'Ожидание запуска',
        status: 'Связь не проверена',
        reels: 'Барабаны готовы к передаче',
    },
    spinning: {
        core: 'Пакет в пути',
        kicker: 'Поиск маршрута',
        status: 'Передаём пакет документов',
        reels: 'Документы отправляются по защищённому каналу',
    },
    success: {
        core: 'Маршрут проложен',
        kicker: 'Маршрут проложен',
        status: 'Связь установлена',
        reels: 'Пакет документов успешно обработан',
    },
    failure: {
        core: 'Маршрут не найден',
        kicker: 'Маршрут не найден',
        status: 'Связь не установлена',
        reels: 'Не удалось получить подтверждение',
    },
};

export const createEpdGame = (game) => {
    const elements = {
        sender: game.querySelector('[data-epd-operator]'),
        target: game.querySelector('[data-epd-target-operator]'),
        selectedSender: game.querySelector('[data-epd-selected]'),
        selectedTarget: game.querySelector('[data-epd-selected-target]'),
        reelsPanel: game.querySelector('[data-epd-reels-panel]'),
        reels: [...game.querySelectorAll('[data-epd-reel]')],
        spinButton: game.querySelector('[data-epd-spin]'),
        spinLabel: game.querySelector('[data-epd-spin-label]'),
        controlHint: game.querySelector('[data-epd-control-hint]'),
        reelHint: game.querySelector('[data-epd-hint]'),
        coreState: game.querySelector('[data-epd-core-state]'),
        result: game.querySelector('[data-epd-result]'),
        resultKicker: game.querySelector('[data-epd-result-kicker]'),
        resultSender: game.querySelector('[data-epd-result-sender]'),
        resultOperator: game.querySelector('[data-epd-result-operator]'),
        status: game.querySelector('[data-epd-status]'),
        announcement: game.querySelector('[data-epd-announcement]'),
        outcomeDialog: game.querySelector('[data-epd-outcome-dialog]'),
    };
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const media = createGameMedia({ game, reducedMotion });
    const reelController = createReelController({
        reducedMotion,
        playStopSound: () => media.playSound('stop', true),
    });
    const state = {
        phase: 'idle',
        sender: elements.sender.value,
        target: elements.target.value,
        hasPlayed: false,
    };
    let outcomeCloseTimer = null;

    const closeOutcomeDialog = () => {
        window.clearTimeout(outcomeCloseTimer);
        outcomeCloseTimer = null;
        elements.outcomeDialog.dataset.visible = 'false';
        elements.outcomeDialog.setAttribute('aria-hidden', 'true');
    };

    const renderState = () => {
        const copy = COPY[state.phase];
        const spinning = state.phase === 'spinning';
        const resultState = ['success', 'failure'].includes(state.phase) ? state.phase : (spinning ? 'searching' : 'idle');

        game.dataset.state = state.phase;
        elements.result.dataset.state = resultState;
        elements.reelsPanel.setAttribute('aria-busy', spinning ? 'true' : 'false');
        elements.sender.disabled = spinning;
        elements.target.disabled = spinning;
        elements.spinButton.disabled = !canStartRound(state);
        elements.spinLabel.textContent = state.hasPlayed ? 'Попробовать ещё' : 'Проверить связь';
        elements.selectedSender.textContent = state.sender || 'Оператор не выбран';
        elements.selectedTarget.textContent = state.target || 'Оператор не выбран';
        elements.resultSender.textContent = state.sender || 'Оператор не выбран';
        elements.resultOperator.textContent = state.target || 'Оператор не выбран';
        elements.coreState.textContent = copy.core;
        elements.resultKicker.textContent = copy.kicker;
        elements.status.textContent = copy.status;
        elements.reelHint.textContent = copy.reels;
        elements.outcomeDialog.dataset.state = state.phase;

        if (['success', 'failure'].includes(state.phase)) {
            elements.announcement.textContent = `${copy.kicker}. Маршрут: ${state.sender} — ${state.target}. ${copy.status}`;
            elements.outcomeDialog.dataset.visible = 'true';
            elements.outcomeDialog.setAttribute('aria-hidden', 'false');
            window.clearTimeout(outcomeCloseTimer);
            outcomeCloseTimer = window.setTimeout(() => closeOutcomeDialog(), 4000);
        } else {
            elements.announcement.textContent = copy.status;
            closeOutcomeDialog();
        }

        if (spinning) {
            elements.controlHint.textContent = 'Подождите, устанавливаем соединение';
        } else if (!state.sender) {
            elements.controlHint.textContent = 'Сначала выберите своего оператора ЭПД';
        } else if (!state.target) {
            elements.controlHint.textContent = 'Теперь выберите второго оператора ЭПД';
        } else if (state.hasPlayed) {
            elements.controlHint.textContent = 'Можно изменить одного из операторов или повторить попытку';
        } else {
            elements.controlHint.textContent = 'Нажмите кнопку, чтобы начать обмен документами';
        }

        media.renderPhase(state.phase);
    };

    const handleOperatorChange = () => {
        if (state.phase === 'spinning') {
            return;
        }

        state.sender = elements.sender.value;
        state.target = elements.target.value;
        state.hasPlayed = false;
        state.phase = getSelectionPhase(state.sender, state.target);
        renderState();
    };

    const runRound = async () => {
        if (!canStartRound(state)) {
            return;
        }

        state.phase = 'spinning';
        renderState();
        media.stopSounds();
        media.playSound('pull');

        await wait(reducedMotion.matches ? 30 : 180);
        const outcome = createRoundOutcome({
            sender: state.sender,
            target: state.target,
        });
        await Promise.all(elements.reels.map((reel, index) => (
            reelController.spin(reel, index, outcome.documents[index])
        )));

        state.phase = outcome.connected ? 'success' : 'failure';
        state.hasPlayed = true;
        renderState();
        media.playSound(outcome.connected ? 'success' : 'failure');
    };

    elements.sender.addEventListener('change', handleOperatorChange);
    elements.target.addEventListener('change', handleOperatorChange);
    elements.spinButton.addEventListener('click', runRound);
    elements.spinButton.addEventListener('mouseenter', () => media.showRetry());
    elements.spinButton.addEventListener('focus', () => media.showRetry());
    state.phase = getSelectionPhase(state.sender, state.target);
    renderState();

    return { renderState, runRound };
};
