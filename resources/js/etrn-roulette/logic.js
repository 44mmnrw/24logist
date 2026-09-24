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

export const LOGISTRU_COMBINATION_WEIGHT = 3;

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
    if (roll < 0.05) {
        return {
            matched: true,
            bonus: true,
            jackpot: true,
            reels: Array(3).fill(LOGISTRU_SYMBOL),
            destination: null,
        };
    }

    if (roll < 0.30) {
        const destination = operators[randomIndex(operators.length, random)];
        return { matched: true, bonus: false, jackpot: false, reels: Array(3).fill(destination), destination };
    }

    const symbols = [
        ...operators,
        ...Array(LOGISTRU_COMBINATION_WEIGHT).fill(LOGISTRU_SYMBOL),
    ];
    const reels = Array.from({ length: 3 }, () => symbols[randomIndex(symbols.length, random)]);
    let bonusSeen = false;
    reels.forEach((symbol, index) => {
        if (symbol.id !== LOGISTRU_SYMBOL.id) return;
        if (bonusSeen) reels[index] = operators[randomIndex(operators.length, random)];
        bonusSeen = true;
    });

    const accidentalWin = reels.every(({ id }) => id === reels[0].id);
    if (accidentalWin) {
        const next = (operators.findIndex(({ id }) => id === reels[0].id) + 1) % operators.length;
        reels[2] = operators[next];
    }

    return { matched: false, bonus: false, jackpot: false, reels, destination: null };
};
