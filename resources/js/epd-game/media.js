import lottie from 'lottie-web';
import routeSearchAnimationData from '../../icons/wired-outline-3366-road-hover-pinch.json';
import routeFailureAnimationData from '../../icons/wired-lineal-926-road-barrier-hover-pinch.json';
import routeSuccessAnimationData from '../../icons/wired-lineal-11-link-hover-bounce.json';
import retryAnimationData from '../../icons/wired-lineal-213-three-arrows-rotate-hover-pinch.json';

const cloneAnimationData = (animationData) => (
    typeof structuredClone === 'function'
        ? structuredClone(animationData)
        : JSON.parse(JSON.stringify(animationData))
);

const loadLottie = (container, animationData, loop = false) => lottie.loadAnimation({
    container,
    renderer: 'svg',
    loop,
    autoplay: false,
    animationData: cloneAnimationData(animationData),
    rendererSettings: { preserveAspectRatio: 'xMidYMid meet' },
});

export const createGameMedia = ({ game, reducedMotion }) => {
    const soundButton = game.querySelector('[data-epd-sound]');
    const soundLabel = game.querySelector('[data-epd-sound-label]');
    const activeSounds = new Set();
    const activeBufferSources = new Set();
    const sounds = {
        pull: new Audio(game.dataset.soundPull),
        stop: new Audio(game.dataset.soundStop),
        success: new Audio(game.dataset.soundSuccess),
        failure: new Audio(game.dataset.soundFailure),
    };
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    const audioFilePromises = Object.fromEntries(Object.entries(sounds).map(([name, audio]) => [
        name,
        window.fetch(audio.src, { cache: 'force-cache' })
            .then((response) => (response.ok ? response.arrayBuffer() : null))
            .catch(() => null),
    ]));
    const audioBuffers = new Map();
    const pendingDecodes = new Set();
    let audioContext = null;
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

    const ensureAudioContext = () => {
        if (!AudioContextClass) {
            return null;
        }

        audioContext ||= new AudioContextClass();
        return audioContext;
    };

    const decodeSounds = (context) => {
        Object.entries(audioFilePromises).forEach(([name, audioFilePromise]) => {
            if (audioBuffers.has(name) || pendingDecodes.has(name)) {
                return;
            }

            pendingDecodes.add(name);
            audioFilePromise
                .then((audioData) => (audioData ? context.decodeAudioData(audioData.slice(0)) : null))
                .then((buffer) => {
                    if (buffer) {
                        audioBuffers.set(name, buffer);
                    }
                })
                .catch(() => {})
                .finally(() => pendingDecodes.delete(name));
        });
    };

    const unlock = () => {
        if (muted) {
            return;
        }

        const context = ensureAudioContext();
        if (!context) {
            return;
        }

        context.resume().catch(() => {});
        decodeSounds(context);

        const silentSource = context.createBufferSource();
        silentSource.buffer = context.createBuffer(1, 1, context.sampleRate);
        silentSource.connect(context.destination);
        silentSource.start(0);
    };

    const renderSoundState = () => {
        soundButton.dataset.muted = muted ? 'true' : 'false';
        soundButton.setAttribute('aria-pressed', muted ? 'true' : 'false');
        soundLabel.textContent = muted ? 'Звук выкл.' : 'Звук вкл.';
    };

    const stopSounds = () => {
        activeBufferSources.forEach((source) => {
            try {
                source.stop();
            } catch {
                // The source may already have stopped naturally.
            }
        });
        activeBufferSources.clear();

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

        if (audioContext?.state === 'running' && audioBuffers.has(name)) {
            const source = audioContext.createBufferSource();
            const gain = audioContext.createGain();
            source.buffer = audioBuffers.get(name);
            gain.gain.value = sounds[name].volume;
            source.connect(gain);
            gain.connect(audioContext.destination);
            activeBufferSources.add(source);
            source.addEventListener('ended', () => activeBufferSources.delete(source), { once: true });
            source.start(0);
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
        } else {
            unlock();
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
        unlock,
    };
};
