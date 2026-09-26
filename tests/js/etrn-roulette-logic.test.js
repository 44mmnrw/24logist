import test from 'node:test';
import assert from 'node:assert/strict';
import { LOGISTRU_SYMBOL, describeOutcome, makeOperators } from '../../resources/js/etrn-roulette/logic.js';

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

test('every winning combination uses the same status text', () => {
    const operator = describeOutcome({ matched: true, jackpot: false, destination: { name: 'Контур' } });
    const jackpot = describeOutcome({ matched: true, jackpot: true, destination: null });

    assert.equal(operator.status, 'ЭТрН отправляется к оператору');
    assert.equal(jackpot.status, operator.status);
    assert.equal(operator.result, 'Контур');
    assert.equal(jackpot.result, 'Супер приз!!!');
    assert.equal(jackpot.longResult, false);
});
