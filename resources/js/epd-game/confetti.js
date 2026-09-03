const COLORS = ['#2767db', '#18b45d', '#f5b83d', '#28aee7', '#e76968', '#52d88d'];
const EFFECT_DURATION = 5400;

const randomBetween = (minimum, maximum) => minimum + (Math.random() * (maximum - minimum));

const createParticle = (element, index, viewportWidth, viewportHeight) => {
    const size = randomBetween(8, 14);
    const particle = {
        element,
        x: randomBetween(-20, viewportWidth + 20),
        y: randomBetween(-viewportHeight * 0.72, -30),
        speedY: randomBetween(1.4, 3.1),
        gravity: randomBetween(0.022, 0.038),
        resistance: randomBetween(0.992, 0.997),
        wind: randomBetween(-0.18, 0.18),
        sway: randomBetween(1.1, 2.8),
        tiltAngle: randomBetween(0, Math.PI * 2),
        tiltAngleIncremental: randomBetween(0.035, 0.095),
        rotation: randomBetween(0, 360),
        rotationSpeed: randomBetween(-7, 7),
    };

    element.style.width = `${size}px`;
    element.style.height = `${size * randomBetween(1.35, 1.85)}px`;
    element.style.background = COLORS[index % COLORS.length];
    element.style.borderRadius = index % 4 === 0 ? '50%' : '3px';

    return particle;
};

const updateConfetti = (particle, frameScale) => {
    // Gravity and air resistance control the vertical fall.
    particle.speedY += particle.gravity * frameScale;
    particle.speedY *= particle.resistance ** frameScale;
    particle.y += particle.speedY * frameScale;

    // The sine wave imitates wind and the irregular shape of a paper particle.
    particle.x += ((Math.sin(particle.tiltAngle) * particle.sway) + particle.wind) * frameScale;
    particle.tiltAngle += particle.tiltAngleIncremental * frameScale;

    // Each particle rotates independently in the air.
    particle.rotation += particle.rotationSpeed * frameScale;
};

export const createConfettiController = ({ game, reducedMotion }) => {
    const elements = [...game.querySelectorAll('.epd-game__confetti i')];
    let animationFrame = null;

    const stop = () => {
        if (animationFrame !== null) {
            window.cancelAnimationFrame(animationFrame);
            animationFrame = null;
        }

        elements.forEach((element) => {
            element.style.opacity = '0';
            element.style.transform = 'translate3d(-100px, -100px, 0)';
        });
    };

    const start = () => {
        stop();
        if (reducedMotion.matches || elements.length === 0) {
            return;
        }

        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const particles = elements.map((element, index) => (
            createParticle(element, index, viewportWidth, viewportHeight)
        ));
        const startedAt = performance.now();
        let previousFrame = startedAt;

        const renderFrame = (now) => {
            const elapsed = now - startedAt;
            const frameScale = Math.min((now - previousFrame) / 16.667, 2.2);
            const opacity = elapsed > EFFECT_DURATION - 900
                ? Math.max(0, (EFFECT_DURATION - elapsed) / 900)
                : 1;
            previousFrame = now;

            particles.forEach((particle) => {
                updateConfetti(particle, frameScale);
                particle.element.style.opacity = String(opacity);
                particle.element.style.transform = `translate3d(${particle.x}px, ${particle.y}px, 0) rotate(${particle.rotation}deg) rotateY(${Math.sin(particle.tiltAngle) * 72}deg)`;
            });

            if (elapsed < EFFECT_DURATION) {
                animationFrame = window.requestAnimationFrame(renderFrame);
            } else {
                stop();
            }
        };

        animationFrame = window.requestAnimationFrame(renderFrame);
    };

    stop();

    return { start, stop };
};
