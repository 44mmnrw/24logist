const SHORT_NAMES = Object.freeze({
    'ПФ СКБ Контур': 'Контур',
    'Калуга Астрал': 'Астрал',
    'Компания Тензор': 'Тензор',
    'Эвотор ОФД': 'Эвотор',
    'ОПЕРАТОР-ЦРПТ': 'ЦРПТ',
    'Энергетические системы и коммуникации': 'ЭСК',
    'НТЦ СТЭК': 'СТЭК',
});

// Change only the values on the right: keys must match config/epd_operators.php.
const DISPLAY_NAMES = Object.freeze({
    'ПФ СКБ Контур': 'Контур',
    'Калуга Астрал': 'Астрал',
    'Эдивеб': 'Эдивеб',
    'Такском': 'Такском',
    'СберКорус': 'СберКорус',
    'Компания Тензор': 'Саби',
    'Эвотор ОФД': 'Эвотор',
    'ФораПром': 'ФОРА',
    'Айтиком': 'Айтиком',
    'ОПЕРАТОР-ЦРПТ': 'ЦРПТ',
    'Точка': 'Точка',
    'НТСсофт': 'МИГ24',
    'Энергетические системы и коммуникации': 'Первый ОФД',
    'НИИАС': 'НИИАС',
    'АТИ-Доки': 'АТИ-Доки',
    'НТЦ СТЭК': 'НТЦ СТЭК',
});

const LOGOS = Object.freeze({
    'ПФ СКБ Контур': 'kontur',
    'Калуга Астрал': 'astral',
    'Компания Тензор': 'saby',
    'Эдивеб': 'ediveb',
    'Эвотор ОФД': 'evotor',
    'Такском': 'taxcom',
    'СберКорус': 'sber',
    'Точка': 'tochka',
    'НТСсофт': 'mig',
    'Айтиком': 'itcom',
    'НТЦ СТЭК': 'stek',
    'Энергетические системы и коммуникации': 'oneofd',
    'АТИ-Доки': 'atidoki',
    'НИИАС': 'niias',
    'ОПЕРАТОР-ЦРПТ': 'crpt',
});

export const LOGISTRU_SYMBOL = Object.freeze({
    id: 'logistru-bonus',
    name: 'Бонус логистРу',
    shortName: 'логистРу',
    logo: 'logistru',
});

export const LOGISTRU_COMBINATION_WEIGHT = 3;

export const makeOperators = (names) => names.map((name) => ({
    id: name,
    name: DISPLAY_NAMES[name] || name,
    shortName: SHORT_NAMES[name] || DISPLAY_NAMES[name] || name,
    logo: LOGOS[name] || null,
}));

export const describeOutcome = (outcome) => ({
    status: outcome.matched ? 'ЭТрН отправляется к оператору' : 'Без выигрыша',
    result: outcome.jackpot ? 'Супер приз!!!' : outcome.matched ? outcome.destination.name : 'Попробуйте ещё раз',
    longResult: Boolean(outcome.destination && outcome.destination.name.length > 26),
});
