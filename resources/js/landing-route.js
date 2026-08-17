const route = document.querySelector('[data-landing-route]');

if (route) {
    const overlay = route.querySelector('[data-landing-route-overlay]');
    const svg = route.querySelector('[data-landing-route-svg]');
    const routePaths = [...route.querySelectorAll('[data-landing-route-path]')];
    const progressPath = route.querySelector('[data-landing-route-progress]');
    const markersLayer = route.querySelector('[data-landing-route-markers]');
    const stops = [...route.querySelectorAll('[data-route-stop]')];

    if (!overlay || !svg || !routePaths.length || !progressPath || !markersLayer || stops.length < 2) {
        overlay?.setAttribute('hidden', '');
    } else {
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let points = [];
        let pathLength = 0;
        let geometryFrame = 0;
        let scrollFrame = 0;
        let finishConnectorStartY = 0;
        let finishConnectorEndY = 0;

        const finishBoxElement = stops[stops.length - 1].querySelector('.final-cta__box');
        const finishConnector = finishBoxElement ? document.createElement('div') : null;
        const finishConnectorProgress = finishConnector
            ? document.createElement('span')
            : null;

        if (finishConnector && finishConnectorProgress) {
            finishConnector.className = 'landing-route__finish-connector';
            finishConnector.innerHTML = `
                <span class="landing-route__finish-road-edge"></span>
                <span class="landing-route__finish-road-surface"></span>
                <span class="landing-route__finish-road-marking"></span>
            `;
            finishConnectorProgress.className = 'landing-route__finish-road-progress';
            finishConnector.append(finishConnectorProgress);
            markersLayer.prepend(finishConnector);
        }

        const markers = stops.map((stop, index) => {
            const marker = document.createElement('div');
            marker.className = 'landing-route__marker';
            marker.classList.toggle('landing-route__marker--start', index === 0);
            marker.classList.toggle('landing-route__marker--finish', index === stops.length - 1);
            marker.innerHTML = `
                <span class="landing-route__marker-icon">
                    <svg viewBox="0 0 16 16" focusable="false">
                        <use href="#icon-doc-check-circle"></use>
                    </svg>
                </span>
                <span class="landing-route__marker-label"></span>
            `;
            marker.querySelector('.landing-route__marker-label').textContent = stop.dataset.routeLabel;
            markersLayer.append(marker);

            return marker;
        });

        const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

        const pathLengthAtY = targetY => {
            if (targetY <= points[0].y) {
                return 0;
            }

            if (targetY >= points[points.length - 1].y) {
                return pathLength;
            }

            let lowerLength = 0;
            let upperLength = pathLength;

            for (let iteration = 0; iteration < 16; iteration += 1) {
                const middleLength = (lowerLength + upperLength) / 2;
                const middlePoint = progressPath.getPointAtLength(middleLength);

                if (middlePoint.y < targetY) {
                    lowerLength = middleLength;
                } else {
                    upperLength = middleLength;
                }
            }

            return (lowerLength + upperLength) / 2;
        };

        const createPath = (routePoints) => routePoints.reduce((path, point, index) => {
            if (index === 0) {
                return `M ${point.x} ${point.y}`;
            }

            const previous = routePoints[index - 1];
            const transitionStart = Math.min(
                point.y - 32,
                Math.max(previous.y + 32, previous.exitY),
            );
            const transitionEnd = Math.max(
                transitionStart,
                Math.min(point.y, point.entryY ?? point.y - 72),
            );
            const middleY = transitionStart + ((transitionEnd - transitionStart) / 2);

            return `${path} L ${previous.x} ${transitionStart} C ${previous.x} ${middleY}, ${point.x} ${middleY}, ${point.x} ${transitionEnd} L ${point.x} ${point.y}`;
        }, '');

        const updateProgress = () => {
            scrollFrame = 0;

            if (!points.length || !pathLength) {
                return;
            }

            const routeRect = route.getBoundingClientRect();
            const threshold = -routeRect.top + (window.innerHeight * 0.5);
            const visiblePathLength = pathLengthAtY(threshold);
            const activeIndex = points.reduce(
                (current, point, index) => point.y <= threshold ? index : current,
                0,
            );

            progressPath.style.strokeDashoffset = String(pathLength - visiblePathLength);

            if (finishConnectorProgress) {
                const connectorProgress = clamp(
                    (threshold - finishConnectorStartY) / Math.max(1, finishConnectorEndY - finishConnectorStartY),
                    0,
                    1,
                );
                finishConnectorProgress.style.height = `${connectorProgress * 100}%`;
            }

            markers.forEach((marker, index) => {
                marker.classList.toggle('is-complete', index < activeIndex);
                marker.classList.toggle('is-active', index === activeIndex);
            });
        };

        const requestProgressUpdate = () => {
            if (!scrollFrame) {
                scrollFrame = window.requestAnimationFrame(updateProgress);
            }
        };

        const updateGeometry = () => {
            geometryFrame = 0;

            const routeRect = route.getBoundingClientRect();
            const routeHeight = route.scrollHeight;
            const mobile = window.matchMedia('(max-width: 760px)').matches;

            points = stops.map((stop, index) => {
                const stopRect = stop.getBoundingClientRect();
                const heading = stop.querySelector('.landing-hero__title, h2, h1');
                const headingRect = heading?.getBoundingClientRect();
                const finishBox = index === stops.length - 1
                    ? stop.querySelector('.final-cta__box')?.getBoundingClientRect()
                    : null;
                const fallbackY = stopRect.top - routeRect.top + clamp(stopRect.height * 0.12, 72, 120);
                const pointY = finishBox
                    ? finishBox.top - routeRect.top + (finishBox.height / 2)
                    : headingRect
                        ? headingRect.top - routeRect.top + (headingRect.height / 2)
                        : fallbackY;
                const finishX = finishBox
                    ? finishBox.left - routeRect.left + (finishBox.width / 2)
                    : null;
                const preferredX = finishX ?? (index % 2 === 0
                    ? (headingRect?.left ?? stopRect.left) - routeRect.left - 34
                    : (headingRect?.right ?? stopRect.right) - routeRect.left + 34);
                const alternateX = finishX ?? (index % 2 === 0
                    ? (headingRect?.right ?? stopRect.right) - routeRect.left + 34
                    : (headingRect?.left ?? stopRect.left) - routeRect.left - 34);
                const markerRadius = index === 0 || index === stops.length - 1 ? 21 : 18;
                const visualRects = [...stop.querySelectorAll('.landing-icon, [class$="__icon"], img')]
                    .map(element => element.getBoundingClientRect())
                    .filter(rect => rect.width > 0 && rect.height > 0);
                const overlapsVisual = candidateX => visualRects.some(rect => {
                    const left = rect.left - routeRect.left;
                    const right = rect.right - routeRect.left;
                    const top = rect.top - routeRect.top;
                    const bottom = rect.bottom - routeRect.top;

                    return candidateX + markerRadius > left
                        && candidateX - markerRadius < right
                        && pointY + markerRadius > top
                        && pointY - markerRadius < bottom;
                });
                const desktopX = overlapsVisual(preferredX) && !overlapsVisual(alternateX)
                    ? alternateX
                    : preferredX;

                return {
                    x: finishBox
                        ? clamp(finishX, 24, routeRect.width - 24)
                        : mobile
                        ? 18
                        : clamp(desktopX, 24, routeRect.width - 24),
                    y: pointY,
                    exitY: stopRect.bottom - routeRect.top - 48,
                    entryY: finishBox ? finishBox.top - routeRect.top : null,
                };
            });

            svg.setAttribute('viewBox', `0 0 ${routeRect.width} ${routeHeight}`);
            svg.setAttribute('width', String(routeRect.width));
            svg.setAttribute('height', String(routeHeight));

            const path = createPath(points);
            routePaths.forEach(routePath => routePath.setAttribute('d', path));
            pathLength = progressPath.getTotalLength();
            progressPath.style.strokeDasharray = String(pathLength);

            markers.forEach((marker, index) => {
                marker.style.left = `${points[index].x}px`;
                marker.style.top = `${points[index].y}px`;
            });

            if (finishConnector && finishBoxElement) {
                const finishBoxRect = finishBoxElement.getBoundingClientRect();
                finishConnectorStartY = finishBoxRect.top - routeRect.top;
                finishConnectorEndY = points[points.length - 1].y;
                finishConnector.style.left = `${points[points.length - 1].x}px`;
                finishConnector.style.top = `${finishConnectorStartY}px`;
                finishConnector.style.height = `${Math.max(0, finishConnectorEndY - finishConnectorStartY)}px`;
            }

            overlay.classList.add('is-ready');
            updateProgress();
        };

        const requestGeometryUpdate = () => {
            if (!geometryFrame) {
                geometryFrame = window.requestAnimationFrame(updateGeometry);
            }
        };

        window.addEventListener('scroll', requestProgressUpdate, { passive: true });
        window.addEventListener('resize', requestGeometryUpdate, { passive: true });
        window.addEventListener('load', requestGeometryUpdate, { once: true });
        reducedMotion.addEventListener?.('change', requestProgressUpdate);

        if ('ResizeObserver' in window) {
            new ResizeObserver(requestGeometryUpdate).observe(route);
        }

        if (document.fonts?.ready) {
            document.fonts.ready.then(requestGeometryUpdate);
        }

        requestGeometryUpdate();
    }
}
