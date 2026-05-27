function initHeroCarousel(root) {
    const slides = [...root.querySelectorAll('[data-hero-carousel-slide]')];

    if (slides.length === 0) {
        return;
    }

    const delay = Math.max(2000, Number.parseInt(root.dataset.delay, 10) || 5000);
    const dots = [...root.querySelectorAll('[data-hero-carousel-dot]')];
    let index = slides.findIndex((slide) => slide.classList.contains('is-active'));

    if (index < 0) {
        index = 0;
        slides[0]?.classList.add('is-active');
        dots[0]?.classList.add('is-active');
    }

    const setSlide = (nextIndex) => {
        index = (nextIndex + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === index);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });
    };

    let timer = null;

    const restartAutoplay = () => {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }

        if (slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        timer = window.setInterval(() => {
            setSlide(index + 1);
        }, delay);
    };

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            setSlide(i);
            restartAutoplay();
        });
    });

    restartAutoplay();
}

function bootHeroCarousels() {
    document.querySelectorAll('[data-hero-carousel]').forEach(initHeroCarousel);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootHeroCarousels);
} else {
    bootHeroCarousels();
}
