document.querySelectorAll('[data-product-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-product-track]');
    const slides = [...carousel.querySelectorAll('[data-product-slide]')];
    const dots = [...carousel.querySelectorAll('[data-product-select]')];
    if (!(track instanceof HTMLElement) || slides.length < 2 || dots.length !== slides.length) return;

    let currentIndex = 0;
    let touchStart = null;
    let timer = null;
    let inView = false;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const hoverMedia = window.matchMedia('(hover: hover)');

    const loadImage = (index) => {
        const image = slides[index]?.querySelector('img');
        if (image) image.loading = 'eager';
    };

    const showSlide = (index) => {
        if (index < 0 || index >= slides.length || index === currentIndex) return;

        loadImage(index);
        loadImage(index + 1);
        currentIndex = index;
        track.style.transform = `translate3d(-${index * 100}%, 0, 0)`;

        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === index;
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
        });
        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === index;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-pressed', String(active));
        });
    };

    const stopAutoplay = () => {
        if (timer === null) return;
        window.clearTimeout(timer);
        timer = null;
    };

    const restartAutoplay = () => {
        stopAutoplay();
        if (
            !inView || document.hidden || reducedMotion.matches
            || (hoverMedia.matches && carousel.matches(':hover'))
            || carousel.querySelector(':focus-visible')
        ) return;

        timer = window.setTimeout(() => {
            showSlide((currentIndex + 1) % slides.length);
            restartAutoplay();
        }, 6000);
    };

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            restartAutoplay();
        });
        dot.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            event.preventDefault();
            const next = (index + (event.key === 'ArrowRight' ? 1 : -1) + slides.length) % slides.length;
            showSlide(next);
            dots[next].focus();
            restartAutoplay();
        });
    });

    track.addEventListener('touchstart', (event) => {
        stopAutoplay();
        const touch = event.touches[0];
        touchStart = touch ? { x: touch.clientX, y: touch.clientY } : null;
    }, { passive: true });
    track.addEventListener('touchend', (event) => {
        if (!touchStart) return;
        const touch = event.changedTouches[0];
        const dx = touch.clientX - touchStart.x;
        const dy = touch.clientY - touchStart.y;
        touchStart = null;
        if (Math.abs(dx) >= 50 && Math.abs(dx) >= Math.abs(dy) * 1.3) {
            showSlide((currentIndex + (dx < 0 ? 1 : -1) + slides.length) % slides.length);
        }
        restartAutoplay();
    }, { passive: true });
    track.addEventListener('touchcancel', () => {
        touchStart = null;
        restartAutoplay();
    }, { passive: true });

    carousel.addEventListener('mouseenter', restartAutoplay);
    carousel.addEventListener('mouseleave', restartAutoplay);
    carousel.addEventListener('focusin', restartAutoplay);
    carousel.addEventListener('focusout', restartAutoplay);
    document.addEventListener('visibilitychange', restartAutoplay);
    reducedMotion.addEventListener('change', restartAutoplay);

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(([entry]) => {
            inView = entry.intersectionRatio >= 0.4;
            restartAutoplay();
        }, { threshold: 0.4 }).observe(carousel);
    } else {
        inView = true;
        restartAutoplay();
    }
});
