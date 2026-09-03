import lottie from 'lottie-web';
import routeSearchAnimationData from '../icons/wired-outline-3366-road-hover-pinch.json';
import routeFailureAnimationData from '../icons/wired-lineal-926-road-barrier-hover-pinch.json';
import routeSuccessAnimationData from '../icons/wired-lineal-11-link-hover-bounce.json';

const games = document.querySelectorAll('[data-epd-game]');

const DOCUMENTS = [
    { code: 'ЭТрН', label: 'накладная' },
    { code: 'ЭЗЗ', label: 'заказ-заявка' },
    { code: 'ЭПЭ', label: 'поручение' },
];

const randomFloat = () => {
    if (window.crypto?.getRandomValues) {
        const value = new Uint32Array(1);
        window.crypto.getRandomValues(value);

        return value[0] / 4294967296;
    }

    return Math.random();
};

const randomItem = (items) => items[Math.floor(randomFloat() * items.length)];

const shuffledItems = (items) => {
    const shuffled = [...items];

    for (let index = shuffled.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(randomFloat() * (index + 1));
        [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
    }

    return shuffled;
};

games.forEach((game) => {
    const operatorSelect = game.querySelector('[data-epd-operator]');
    const selectedOperator = game.querySelector('[data-epd-selected]');
    const reelsPanel = game.querySelector('[data-epd-reels-panel]');
    const reels = [...game.querySelectorAll('[data-epd-reel]')];
    const spinButton = game.querySelector('[data-epd-spin]');
    const spinLabel = game.querySelector('[data-epd-spin-label]');
    const controlHint = game.querySelector('[data-epd-control-hint]');
    const reelHint = game.querySelector('[data-epd-hint]');
    const result = game.querySelector('[data-epd-result]');
    const resultKicker = game.querySelector('[data-epd-result-kicker]');
    const resultOperator = game.querySelector('[data-epd-result-operator]');
    const status = game.querySelector('[data-epd-status]');
    const routeAnimationElement = game.querySelector('[data-epd-route-animation]');
    const routeFailureAnimationElement = game.querySelector('[data-epd-route-failure-animation]');
    const routeSuccessAnimationElement = game.querySelector('[data-epd-route-success-animation]');
    const soundButton = game.querySelector('[data-epd-sound]');
    const soundLabel = game.querySelector('[data-epd-sound-label]');
    const operators = [...operatorSelect.options].map((option) => option.value).filter(Boolean);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sounds = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    let isSpinning = false;
    let hasPlayed = false;
    let isMuted = false;
    const routeAnimation = lottie.loadAnimation({
        container: routeAnimationElement,
        renderer: 'svg',
        loop: true,
        autoplay: false,
        animationData: structuredClone(routeSearchAnimationData),
        rendererSettings: {
            preserveAspectRatio: 'xMidYMid meet',
        },
    });
    routeAnimation.goToAndStop(0, true);
    const routeFailureAnimation = lottie.loadAnimation({
        container: routeFailureAnimationElement,
        renderer: 'svg',
        loop: false,
        autoplay: false,
        animationData: structuredClone(routeFailureAnimationData),
        rendererSettings: {
            preserveAspectRatio: 'xMidYMid meet',
        },
    });
    const routeSuccessAnimation = lottie.loadAnimation({
        container: routeSuccessAnimationElement,
        renderer: 'svg',
        loop: false,
        autoplay: false,
        animationData: structuredClone(routeSuccessAnimationData),
        rendererSettings: {
            preserveAspectRatio: 'xMidYMid meet',
        },
    });

    try {
        isMuted = window.localStorage.getItem('epd-game-muted') === 'true';
    } catch {
        isMuted = false;
    }

    Object.values(sounds).forEach((audio) => {
        audio.preload = 'auto';
        audio.volume = 0.42;
    });

    sounds.pull.volume = 0.32;
    sounds.failure.volume = 0.3;

    const renderSoundState = () => {
        soundButton.dataset.muted = isMuted ? 'true' : 'false';
        soundButton.setAttribute('aria-pressed', isMuted ? 'true' : 'false');
        soundLabel.textContent = isMuted ? 'Звук выключен' : 'Звук включён';
    };

    const playSound = (name, overlap = false) => {
        if (isMuted) {
            return;
        }

        const source = sounds[name];
        const audio = overlap ? source.cloneNode() : source;
        audio.volume = source.volume;

        if (!overlap) {
            audio.currentTime = 0;
        }

        audio.play().catch(() => {});
    };

    renderSoundState();

    soundButton.addEventListener('click', () => {
        isMuted = !isMuted;

        try {
            window.localStorage.setItem('epd-game-muted', String(isMuted));
        } catch {
            // The game still works when browser storage is unavailable.
        }

        renderSoundState();

        if (!isMuted) {
            playSound('stop');
        }
    });

    const createReelItem = (documentType) => {
        const item = window.document.createElement('span');
        const code = window.document.createElement('b');
        const label = window.document.createElement('small');

        item.className = 'epd-game__reel-item';
        code.className = 'epd-game__reel-label';
        code.textContent = documentType.code;
        label.textContent = documentType.label;
        item.append(code, label);

        return item;
    };

    const renderReel = (reel, documentType) => {
        const track = reel.querySelector('[data-epd-reel-track]');
        track.style.transition = 'none';
        track.style.transform = 'translate3d(0, 0, 0)';
        track.replaceChildren(createReelItem(documentType));
        reel.setAttribute('aria-label', `${documentType.code} — ${documentType.label}`);
    };

    const spinReel = (reel, index, finalDocument) => new Promise((resolve) => {
        const track = reel.querySelector('[data-epd-reel-track]');
        const visibleCode = track.querySelector('.epd-game__reel-label')?.textContent;
        const firstDocument = DOCUMENTS.find((item) => item.code === visibleCode) || randomItem(DOCUMENTS);
        const itemCount = 29 + (index * 3);
        const sequence = [firstDocument];

        while (sequence.length < itemCount - 1) {
            const previousDocument = sequence[sequence.length - 1];
            const nextDocuments = DOCUMENTS.filter((item) => item.code !== previousDocument.code);
            sequence.push(randomItem(nextDocuments));
        }

        sequence.push(finalDocument);
        track.style.transition = 'none';
        track.style.transform = 'translate3d(0, 0, 0)';
        track.replaceChildren(...sequence.map(createReelItem));
        reel.classList.add('is-rolling');
        let reelAnimation = null;
        let isFinished = false;

        const finish = () => {
            if (isFinished) {
                return;
            }

            isFinished = true;
            reelAnimation?.cancel();
            reel.classList.remove('is-rolling');
            renderReel(reel, finalDocument);
            reel.classList.add('is-stopped');
            playSound('stop', true);
            window.setTimeout(() => reel.classList.remove('is-stopped'), 260);
            resolve();
        };

        if (reducedMotion.matches) {
            window.setTimeout(finish, 120);
            return;
        }

        window.requestAnimationFrame(() => {
            const itemHeight = reel.getBoundingClientRect().height;
            const distance = (itemCount - 1) * itemHeight;
            const spinDuration = 3400 + (index * 360);

            window.requestAnimationFrame(() => {
                if (typeof track.animate !== 'function') {
                    track.style.transition = `transform ${spinDuration}ms cubic-bezier(0.18, 0.68, 0.22, 1)`;
                    track.style.transform = `translate3d(0, -${distance}px, 0)`;
                    window.setTimeout(finish, spinDuration);
                    return;
                }

                reelAnimation = track.animate([
                    {
                        offset: 0,
                        transform: 'translate3d(0, 0, 0)',
                        easing: 'cubic-bezier(0.55, 0, 0.82, 0.45)',
                    },
                    {
                        offset: 0.14,
                        transform: `translate3d(0, -${distance * 0.1}px, 0)`,
                        easing: 'linear',
                    },
                    {
                        offset: 0.76,
                        transform: `translate3d(0, -${distance * 0.82}px, 0)`,
                        easing: 'cubic-bezier(0.16, 0.72, 0.24, 1)',
                    },
                    {
                        offset: 1,
                        transform: `translate3d(0, -${distance}px, 0)`,
                    },
                ], {
                    duration: spinDuration,
                    fill: 'forwards',
                });

                reelAnimation.finished.then(finish).catch(finish);
            });
        });
    });

    const resetResult = () => {
        routeAnimation.goToAndStop(0, true);
        routeFailureAnimation.stop();
        routeSuccessAnimation.stop();
        result.dataset.state = 'idle';
        resultKicker.textContent = 'Ожидание запуска';
        resultOperator.textContent = 'Оператор появится здесь';
        status.textContent = 'Связь не проверена';
    };

    operatorSelect.addEventListener('change', () => {
        const value = operatorSelect.value;
        selectedOperator.textContent = value || 'Оператор не выбран';
        spinButton.disabled = value === '';
        controlHint.textContent = value
            ? 'Нажмите кнопку, чтобы начать обмен документами'
            : 'Сначала выберите своего оператора ЭПД';

        if (!isSpinning && !hasPlayed) {
            resetResult();
        }
    });

    spinButton.addEventListener('click', async () => {
        if (isSpinning || operatorSelect.value === '') {
            return;
        }

        isSpinning = true;
        spinButton.disabled = true;
        operatorSelect.disabled = true;
        reelsPanel.setAttribute('aria-busy', 'true');
        game.classList.add('is-spinning');
        result.dataset.state = 'searching';
        routeFailureAnimation.stop();
        routeSuccessAnimation.stop();
        resultKicker.textContent = 'Поиск маршрута';
        resultOperator.textContent = 'Согласование маршрута…';
        status.textContent = 'Передаём пакет документов';
        reelHint.textContent = 'Документы отправляются по защищённому каналу';
        controlHint.textContent = 'Подождите, устанавливаем соединение';
        if (reducedMotion.matches) {
            routeAnimation.goToAndStop(60, true);
        } else {
            routeAnimation.goToAndPlay(0, true);
        }
        playSound('pull');

        const connected = randomFloat() < 0.7;
        const successfulDocument = randomItem(DOCUMENTS);
        const finalDocuments = connected
            ? reels.map(() => successfulDocument)
            : shuffledItems(DOCUMENTS).slice(0, reels.length);

        await Promise.all(reels.map((reel, index) => spinReel(reel, index, finalDocuments[index])));

        result.dataset.state = connected ? 'success' : 'failure';
        routeAnimation.stop();
        if (connected) {
            if (reducedMotion.matches) {
                routeSuccessAnimation.goToAndStop(routeSuccessAnimation.totalFrames - 1, true);
            } else {
                routeSuccessAnimation.goToAndPlay(0, true);
            }
        } else {
            if (reducedMotion.matches) {
                routeFailureAnimation.goToAndStop(routeFailureAnimation.totalFrames - 1, true);
            } else {
                routeFailureAnimation.goToAndPlay(0, true);
            }
        }
        resultKicker.textContent = connected ? 'Маршрут проложен' : 'Маршрут не найден';
        resultOperator.textContent = randomItem(operators);
        status.textContent = connected ? 'Связь установлена' : 'Связь не установлена';
        reelHint.textContent = connected
            ? 'Пакет документов успешно обработан'
            : 'Не удалось получить подтверждение';
        playSound(connected ? 'success' : 'failure');

        isSpinning = false;
        hasPlayed = true;
        game.classList.remove('is-spinning');
        reelsPanel.setAttribute('aria-busy', 'false');
        operatorSelect.disabled = false;
        spinButton.disabled = false;
        spinLabel.textContent = 'Попробовать ещё';
        controlHint.textContent = 'Можно изменить своего оператора или повторить попытку';
    });
});
