document.querySelectorAll('[data-mobile-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-mobile-slide]')];
    const dots = [...carousel.querySelectorAll('[data-mobile-select]')];
    if (slides.length < 2 || dots.length !== slides.length) return;

    const showSlide = (index) => {
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === index;
            slide.style.transform = `translate3d(${(slideIndex - index) * 100}%, 0, 0)`;
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
        });

        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === index;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-pressed', String(active));
        });
    };

    dots.forEach((dot, index) => dot.addEventListener('click', () => showSlide(index)));

    const showHashTarget = () => {
        const hash = window.location.hash.slice(1);
        const index = slides.findIndex((slide) => slide.firstElementChild?.id === hash);
        if (index >= 0) showSlide(index);
    };

    showHashTarget();
    window.addEventListener('hashchange', showHashTarget);
});
