import test from 'node:test';
import assert from 'node:assert/strict';
import { LOGISTRU_SYMBOL, makeOperators } from '../../resources/js/etrn-roulette/logic.js';

test('operator names and logos are mapped for display', () => {
    const operators = makeOperators(['ПФ СКБ Контур', 'Эдивеб', 'Компания Тензор', 'ФораПром']);
    assert.deepEqual(operators[0], {
        id: 'ПФ СКБ Контур', name: 'ПФ СКБ Контур', shortName: 'Контур', logo: 'kontur',
    });
    assert.equal(operators[1].logo, 'ediveb');
    assert.equal(operators[2].logo, 'saby');
    assert.equal(operators[3].name, 'ФОРА');
});

test('LogistRu bonus has a distinct reel identifier', () => {
    assert.equal(LOGISTRU_SYMBOL.id, 'logistru-bonus');
});
