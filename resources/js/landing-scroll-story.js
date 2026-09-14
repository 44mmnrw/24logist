document.querySelectorAll('[data-scroll-story]').forEach((story) => {
    const stage = story.querySelector('[data-scroll-stage]');
    const slides = [...story.querySelectorAll('[data-scroll-slide]')];
    if (!(stage instanceof HTMLElement) || slides.length < 2) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let segmentDistance = 1;
    let stickyTop = 64;
    let frame = 0;

    const update = () => {
        frame = 0;
        const progress = Math.max(0, Math.min(slides.length - 1, (stickyTop - story.getBoundingClientRect().top) / segmentDistance));
        const visibleProgress = reducedMotion.matches ? Math.round(progress) : progress;

        slides.forEach((slide, index) => {
            slide.style.transform = `translate3d(${(index - visibleProgress) * 100}%, 0, 0)`;
            const active = index === Math.round(progress);
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
        segmentDistance = Math.max(360, Math.min(window.innerHeight * .85, 800));
        story.style.setProperty('--scroll-story-sticky-top', `${stickyTop}px`);
        story.style.height = `${stageHeight + segmentDistance * (slides.length - 1)}px`;
        scheduleUpdate();
    };

    measure();
    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', measure);
    window.addEventListener('pageshow', scheduleUpdate);
    reducedMotion.addEventListener('change', scheduleUpdate);
    new ResizeObserver(measure).observe(stage);
});
