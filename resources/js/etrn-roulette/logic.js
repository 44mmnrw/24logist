const SHORT_NAMES = Object.freeze({
    'ПФ СКБ Контур': 'Контур',
    'Калуга Астрал': 'Астрал',
    'Компания Тензор': 'Тензор',
    'Эвотор ОФД': 'Эвотор',
    'ОПЕРАТОР-ЦРПТ': 'ЦРПТ',
    'Энергетические системы и коммуникации': 'ЭСК',
    'НТЦ СТЭК': 'СТЭК',
});

const LOGOS = Object.freeze({
    'ПФ СКБ Контур': 'kontur',
    'Калуга Астрал': 'astral',
    'Компания Тензор': 'saby',
    'Эдивеб': 'ediveb',
    'Эвотор ОФД': 'evotor',
    'Такском': 'taxcom',
});

export const LOGISTRU_SYMBOL = Object.freeze({
    id: 'logistru-bonus',
    name: 'Бонус ЛогистРу',
    shortName: 'ЛогистРу',
    logo: 'logistru',
});

export const makeOperators = (names) => names.map((name) => ({
    id: name,
    name,
    shortName: SHORT_NAMES[name] || name,
    logo: LOGOS[name] || null,
}));

const randomIndex = (length, random) => Math.min(length - 1, Math.floor(random() * length));

export const createRouletteOutcome = (operators, random = Math.random) => {
    if (operators.length < 2) {
        throw new Error('At least two operators are required');
    }

    const roll = random();
    if (roll < 0.25) {
        const destination = operators[randomIndex(operators.length, random)];
        return { matched: true, bonus: false, reels: Array(3).fill(destination), destination };
    }

    if (roll < 0.35) {
        const destination = operators[randomIndex(operators.length, random)];
        const reels = Array(3).fill(destination);
        reels[randomIndex(reels.length, random)] = LOGISTRU_SYMBOL;
        return { matched: true, bonus: true, reels, destination };
    }

    const symbols = [...operators, LOGISTRU_SYMBOL];
    const reels = Array.from({ length: 3 }, () => symbols[randomIndex(symbols.length, random)]);
    let bonusSeen = false;
    reels.forEach((symbol, index) => {
        if (symbol.id !== LOGISTRU_SYMBOL.id) return;
        if (bonusSeen) reels[index] = operators[randomIndex(operators.length, random)];
        bonusSeen = true;
    });

    const edoReels = reels.filter(({ id }) => id !== LOGISTRU_SYMBOL.id);
    const accidentalWin = reels.every(({ id }) => id === reels[0].id)
        || (edoReels.length === 2 && edoReels[0].id === edoReels[1].id);
    if (accidentalWin) {
        const index = reels[2].id === LOGISTRU_SYMBOL.id ? 1 : 2;
        const next = (operators.findIndex(({ id }) => id === edoReels[0].id) + 1) % operators.length;
        reels[index] = operators[next];
    }

    return { matched: false, bonus: false, reels, destination: null };
};
