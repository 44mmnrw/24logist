import test from 'node:test';
import assert from 'node:assert/strict';
import { createRouletteOutcome, LOGISTRU_SYMBOL, makeOperators } from '../../resources/js/etrn-roulette/logic.js';

const operators = makeOperators(['ПФ СКБ Контур', 'Калуга Астрал', 'Эдивеб', 'Компания Тензор']);

test('matching result has one destination on all three reels', () => {
    const outcome = createRouletteOutcome(operators, () => 0);
    assert.equal(outcome.matched, true);
    assert.equal(outcome.destination.name, 'ПФ СКБ Контур');
    assert.deepEqual(outcome.reels.map(({ id }) => id), Array(3).fill('ПФ СКБ Контур'));
});

test('miss never displays three matching operators', () => {
    const outcome = createRouletteOutcome(operators, () => 0.99);
    assert.equal(outcome.matched, false);
    assert.equal(outcome.destination, null);
    assert.notEqual(new Set(outcome.reels.map(({ id }) => id)).size, 1);
});

test('LogistRu bonus replaces one reel and still picks an EDO destination', () => {
    const values = [0.30, 0, 1 / 3];
    const outcome = createRouletteOutcome(operators, () => values.shift());
    assert.equal(outcome.matched, true);
    assert.equal(outcome.bonus, true);
    assert.equal(outcome.destination.id, operators[0].id);
    assert.equal(outcome.reels.filter(({ id }) => id === LOGISTRU_SYMBOL.id).length, 1);
    assert.equal(outcome.reels.filter(({ id }) => id === operators[0].id).length, 2);
});

test('miss cannot accidentally qualify for the operator or bonus win', () => {
    const values = [0.99, 0, 0, 0.99];
    const outcome = createRouletteOutcome(operators, () => values.shift());
    const edoReels = outcome.reels.filter(({ id }) => id !== LOGISTRU_SYMBOL.id);
    assert.equal(outcome.matched, false);
    assert.equal(outcome.bonus, false);
    assert.equal(outcome.reels.filter(({ id }) => id === LOGISTRU_SYMBOL.id).length, 1);
    assert.notEqual(edoReels[0].id, edoReels[1].id);
});

test('every configured operator can be selected as the destination', () => {
    operators.forEach((operator, index) => {
        const values = [0, (index + 0.5) / operators.length];
        const outcome = createRouletteOutcome(operators, () => values.shift());
        assert.equal(outcome.destination.id, operator.id);
    });
});

test('full operator names are retained alongside short reel labels', () => {
    assert.deepEqual(operators[0], {
        id: 'ПФ СКБ Контур', name: 'ПФ СКБ Контур', shortName: 'Контур', logo: 'kontur',
    });
    assert.deepEqual(operators[2], {
        id: 'Эдивеб', name: 'Эдивеб', shortName: 'Эдивеб', logo: 'ediveb',
    });
    assert.equal(operators[3].logo, 'saby');
});
