const games = [...document.querySelectorAll('[data-epd-game]')];

if (games.length > 0) {
    import('./epd-game/controller.js').then(({ createEpdGame }) => {
        games.forEach((game) => {
            if (game.dataset.epdInitialized === 'true') {
                return;
            }

            game.dataset.epdInitialized = 'true';
            createEpdGame(game);
        });
    });
}
