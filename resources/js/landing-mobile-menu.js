const mobileMenus = document.querySelectorAll('.landing-mobile-menu');

mobileMenus.forEach((menu) => {
    const toggle = menu.querySelector('.landing-mobile-menu__toggle');

    menu.addEventListener('toggle', () => {
        toggle?.setAttribute('aria-label', menu.open ? 'Закрыть меню' : 'Открыть меню');
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => menu.removeAttribute('open'));
    });
});

document.addEventListener('click', (event) => {
    mobileMenus.forEach((menu) => {
        if (menu.open && !menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    mobileMenus.forEach((menu) => {
        if (!menu.open) return;

        menu.removeAttribute('open');
        menu.querySelector('.landing-mobile-menu__toggle')?.focus();
    });
});
