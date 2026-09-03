import lottie from 'lottie-web';
import routeSearchAnimationData from '../../icons/wired-outline-3366-road-hover-pinch.json';
import routeFailureAnimationData from '../../icons/wired-lineal-926-road-barrier-hover-pinch.json';
import routeSuccessAnimationData from '../../icons/wired-lineal-11-link-hover-bounce.json';
import retryAnimationData from '../../icons/wired-lineal-213-three-arrows-rotate-hover-pinch.json';

const loadLottie = (container, animationData, loop = false) => lottie.loadAnimation({
    container,
    renderer: 'svg',
    loop,
    autoplay: false,
    animationData: structuredClone(animationData),
    rendererSettings: { preserveAspectRatio: 'xMidYMid meet' },
});

export const createGameMedia = ({ game, reducedMotion }) => {
    const soundButton = game.querySelector('[data-epd-sound]');
    const soundLabel = game.querySelector('[data-epd-sound-label]');
    const activeSounds = new Set();
    const sounds = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    let muted = false;

    const animations = {
        search: loadLottie(game.querySelector('[data-epd-route-animation]'), routeSearchAnimationData, true),
        failure: loadLottie(game.querySelector('[data-epd-route-failure-animation]'), routeFailureAnimationData),
        success: loadLottie(game.querySelector('[data-epd-route-success-animation]'), routeSuccessAnimationData),
        retry: loadLottie(game.querySelector('[data-epd-retry-animation]'), retryAnimationData),
    };

    animations.search.goToAndStop(0, true);
    animations.retry.goToAndStop(0, true);

    try {
        muted = window.localStorage.getItem('epd-game-muted') === 'true';
    } catch {
        muted = false;
    }

    Object.values(sounds).forEach((audio) => {
        audio.preload = 'auto';
        audio.volume = 0.42;
    });
    sounds.pull.volume = 0.32;
    sounds.failure.volume = 0.3;

    const renderSoundState = () => {
        soundButton.dataset.muted = muted ? 'true' : 'false';
        soundButton.setAttribute('aria-pressed', muted ? 'true' : 'false');
        soundLabel.textContent = muted ? 'Звук выкл.' : 'Звук вкл.';
    };

    const stopSounds = () => {
        activeSounds.forEach((audio) => {
            audio.pause();
            audio.currentTime = 0;
        });
        activeSounds.clear();

        Object.values(sounds).forEach((audio) => {
            audio.pause();
            audio.currentTime = 0;
        });
    };

    const playSound = (name, overlap = false) => {
        if (muted) {
            return;
        }

        const source = sounds[name];
        const audio = overlap ? source.cloneNode() : source;
        audio.volume = source.volume;
        audio.currentTime = 0;

        if (overlap) {
            activeSounds.add(audio);
            audio.addEventListener('ended', () => activeSounds.delete(audio), { once: true });
        }

        audio.play().catch(() => activeSounds.delete(audio));
    };

    const showRetry = ({ animate = true } = {}) => {
        animations.retry.loop = false;
        if (!animate || reducedMotion.matches) {
            animations.retry.goToAndStop(0, true);
        } else {
            animations.retry.goToAndPlay(0, true);
        }
    };

    let renderedPhase = null;
    const renderPhase = (phase) => {
        if (phase === renderedPhase) {
            return;
        }

        renderedPhase = phase;
        animations.search.stop();
        animations.failure.stop();
        animations.success.stop();

        if (phase === 'spinning') {
            animations.retry.loop = true;
            animations.retry.goToAndPlay(0, true);
            if (reducedMotion.matches) {
                animations.search.goToAndStop(60, true);
            } else {
                animations.search.goToAndPlay(0, true);
            }
            return;
        }

        animations.retry.loop = false;
        animations.retry.stop();

        if (phase === 'success') {
            if (reducedMotion.matches) {
                animations.success.goToAndStop(animations.success.totalFrames - 1, true);
            } else {
                animations.success.goToAndPlay(0, true);
            }
            showRetry();
            return;
        }

        if (phase === 'failure') {
            if (reducedMotion.matches) {
                animations.failure.goToAndStop(animations.failure.totalFrames - 1, true);
            } else {
                animations.failure.goToAndPlay(0, true);
            }
            showRetry();
            return;
        }

        animations.search.goToAndStop(0, true);
        showRetry({ animate: false });
    };

    soundButton.addEventListener('click', () => {
        muted = !muted;

        try {
            window.localStorage.setItem('epd-game-muted', String(muted));
        } catch {
            // The game remains usable when browser storage is unavailable.
        }

        if (muted) {
            stopSounds();
        }
        renderSoundState();
        if (!muted) {
            playSound('stop');
        }
    });

    renderSoundState();

    return {
        playSound,
        renderPhase,
        showRetry,
        stopSounds,
    };
};
