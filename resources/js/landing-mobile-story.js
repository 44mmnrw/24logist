document.querySelectorAll('[data-mobile-story]').forEach((story) => {
    const stage = story.querySelector('[data-mobile-story-stage]');
    const slides = [...story.querySelectorAll('[data-mobile-story-slide]')];
    if (!(stage instanceof HTMLElement) || slides.length !== 2) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let scrollDistance = 1;
    let stickyTop = 64;
    let frame = 0;

    const update = () => {
        frame = 0;
        const progress = Math.max(0, Math.min(1, (stickyTop - story.getBoundingClientRect().top) / scrollDistance));
        const visibleProgress = reducedMotion.matches ? Number(progress >= .5) : progress;

        slides[0].style.transform = `translate3d(${-visibleProgress * 100}%, 0, 0)`;
        slides[1].style.transform = `translate3d(${(1 - visibleProgress) * 100}%, 0, 0)`;

        const activeIndex = progress >= .5 ? 1 : 0;
        slides.forEach((slide, index) => {
            const active = index === activeIndex;
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
        });
    };

    const scheduleUpdate = () => {
        if (!frame) frame = requestAnimationFrame(update);
    };

    const measure = () => {
        story.classList.add('is-ready');
        const stageHeight = Math.ceil(stage.getBoundingClientRect().height);
        const headerHeight = document.querySelector('.landing-header')?.getBoundingClientRect().height ?? 64;
        stickyTop = Math.min(headerHeight, window.innerHeight - stageHeight);
        scrollDistance = Math.max(360, Math.min(window.innerHeight * .85, 800));
        story.style.setProperty('--mobile-story-sticky-top', `${stickyTop}px`);
        story.style.height = `${stageHeight + scrollDistance}px`;
        scheduleUpdate();
    };

    measure();
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', measure);
    window.addEventListener('pageshow', scheduleUpdate);
    reducedMotion.addEventListener('change', scheduleUpdate);
    new ResizeObserver(measure).observe(stage);
});
