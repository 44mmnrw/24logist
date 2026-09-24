const game = document.querySelector('[data-etrn-game]');

if (game) {
    import('./etrn-roulette/controller.js').then(({ createEtrnRoulette }) => createEtrnRoulette(game));
}
