const SHORT_NAMES = Object.freeze({
    'ПФ СКБ Контур': 'Контур',
    'Калуга Астрал': 'Астрал',
    'Компания Тензор': 'Тензор',
    'Эвотор ОФД': 'Эвотор',
    'ОПЕРАТОР-ЦРПТ': 'ЦРПТ',
    'Энергетические системы и коммуникации': 'ЭСК',
    'НТЦ СТЭК': 'СТЭК',
});

const DISPLAY_NAMES = Object.freeze({
    'ФораПром': 'ФОРА',
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
    name: 'Бонус ЛогистРу',
    shortName: 'ЛогистРу',
    logo: 'logistru',
});

export const LOGISTRU_COMBINATION_WEIGHT = 3;

export const makeOperators = (names) => names.map((name) => ({
    id: name,
    name: DISPLAY_NAMES[name] || name,
    shortName: SHORT_NAMES[name] || DISPLAY_NAMES[name] || name,
    logo: LOGOS[name] || null,
}));
