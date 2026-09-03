import canvasConfetti from 'canvas-confetti';

const COLORS = ['#2767db', '#18b45d', '#f5b83d', '#28aee7', '#e76968', '#52d88d'];
const EFFECT_DURATION = 5400;

export const createConfettiController = ({ game, reducedMotion }) => {
    const canvas = game.querySelector('[data-epd-confetti]');
    const fire = canvas
        ? canvasConfetti.create(canvas, { resize: true, useWorker: true })
        : null;
    let animationFrame = null;

    const stop = () => {
        if (animationFrame !== null) {
            window.cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }

        fire?.reset();
    };

    const start = () => {
        stop();
        if (!fire || reducedMotion.matches) {
            return;
        }

        const commonOptions = {
            colors: COLORS,
            disableForReducedMotion: true,
            decay: 0.94,
            gravity: 0.55,
            scalar: 1.15,
            ticks: 420,
            zIndex: 1002,
        };

        fire({
            ...commonOptions,
            particleCount: 120,
            spread: 105,
            startVelocity: 38,
            origin: { x: 0.5, y: 0.25 },
        });

        const finishAt = performance.now() + EFFECT_DURATION;
        const renderFrame = (now) => {
            fire({
                ...commonOptions,
                particleCount: 2,
                angle: 62,
                spread: 52,
                startVelocity: 30,
                origin: { x: 0, y: 0.66 },
            });
            fire({
                ...commonOptions,
                particleCount: 2,
                angle: 118,
                spread: 52,
                startVelocity: 30,
                origin: { x: 1, y: 0.66 },
            });

            if (now < finishAt) {
                animationFrame = window.requestAnimationFrame(renderFrame);
            } else {
                animationFrame = null;
            }
        };

        animationFrame = window.requestAnimationFrame(renderFrame);
    };

    return { start, stop };
};
