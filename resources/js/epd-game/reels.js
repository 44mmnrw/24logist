import { DOCUMENTS, randomItem } from './logic.js';

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

export const createReelController = ({ reducedMotion, playStopSound }) => {
    const render = (reel, documentType) => {
        const track = reel.querySelector('[data-epd-reel-track]');
        track.style.transition = 'none';
        track.style.transform = 'translate3d(0, 0, 0)';
        track.replaceChildren(createReelItem(documentType));
        reel.setAttribute('aria-label', `${documentType.code} — ${documentType.label}`);
    };

    const spin = (reel, index, finalDocument) => new Promise((resolve) => {
        const track = reel.querySelector('[data-epd-reel-track]');
        const visibleCode = track.querySelector('.epd-game__reel-label')?.textContent;
        const firstDocument = DOCUMENTS.find((item) => item.code === visibleCode) || randomItem(DOCUMENTS);
        const itemCount = 23 + (index * 3);
        const sequence = [firstDocument];

        while (sequence.length < itemCount - 1) {
            const previousDocument = sequence[sequence.length - 1];
            const alternatives = DOCUMENTS.filter((item) => item.code !== previousDocument.code);
            sequence.push(randomItem(alternatives));
        }

        sequence.push(finalDocument);
        track.style.transition = 'none';
        track.style.transform = 'translate3d(0, 0, 0)';
        track.replaceChildren(...sequence.map(createReelItem));
        reel.classList.add('is-rolling');

        let animation = null;
        let finished = false;

        const finish = () => {
            if (finished) {
                return;
            }

            finished = true;
            animation?.cancel();
            reel.classList.remove('is-rolling');
            render(reel, finalDocument);
            reel.classList.add('is-stopped');
            playStopSound();
            window.setTimeout(() => reel.classList.remove('is-stopped'), 260);
            resolve();
        };

        if (reducedMotion.matches) {
            window.setTimeout(finish, 100);
            return;
        }

        window.requestAnimationFrame(() => {
            const itemHeight = reel.getBoundingClientRect().height;
            const distance = (itemCount - 1) * itemHeight;
            const duration = 2550 + (index * 260);

            window.requestAnimationFrame(() => {
                if (typeof track.animate !== 'function') {
                    track.style.transition = `transform ${duration}ms cubic-bezier(0.16, 0.72, 0.24, 1)`;
                    track.style.transform = `translate3d(0, -${distance}px, 0)`;
                    window.setTimeout(finish, duration);
                    return;
                }

                animation = track.animate([
                    {
                        offset: 0,
                        transform: 'translate3d(0, 0, 0)',
                        easing: 'cubic-bezier(0.55, 0, 0.82, 0.45)',
                    },
                    {
                        offset: 0.15,
                        transform: `translate3d(0, -${distance * 0.1}px, 0)`,
                        easing: 'linear',
                    },
                    {
                        offset: 0.78,
                        transform: `translate3d(0, -${distance * 0.82}px, 0)`,
                        easing: 'cubic-bezier(0.16, 0.72, 0.24, 1)',
                    },
                    {
                        offset: 1,
                        transform: `translate3d(0, -${distance}px, 0)`,
                    },
                ], { duration, fill: 'forwards' });

                animation.finished.then(finish).catch(finish);
            });
        });
    });

    return { render, spin };
};
