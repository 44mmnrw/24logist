import test from 'node:test';
import assert from 'node:assert/strict';

import {
    canStartRound,
    createRoundOutcome,
    getSelectionPhase,
} from '../../resources/js/epd-game/logic.js';

test('round is unavailable until both operators are selected', () => {
    assert.equal(canStartRound({ sender: '', target: '', phase: 'idle' }), false);
    assert.equal(canStartRound({ sender: 'A', target: '', phase: 'idle' }), false);
    assert.equal(canStartRound({ sender: 'A', target: 'B', phase: 'ready' }), true);
    assert.equal(canStartRound({ sender: 'A', target: 'B', phase: 'spinning' }), false);
    assert.equal(getSelectionPhase('A', 'B'), 'ready');
});

test('successful round produces three identical documents', () => {
    const values = [0.1, 0.5];
    const outcome = createRoundOutcome({ random: () => values.shift() });

    assert.equal(outcome.connected, true);
    assert.equal(outcome.documents.length, 3);
    assert.equal(new Set(outcome.documents.map(({ code }) => code)).size, 1);
});

test('failed round produces three different documents', () => {
    const outcome = createRoundOutcome({ random: () => 0.9 });

    assert.equal(outcome.connected, false);
    assert.equal(outcome.documents.length, 3);
    assert.equal(new Set(outcome.documents.map(({ code }) => code)).size, 3);
});

test('success threshold remains exactly seventy percent', () => {
    assert.equal(createRoundOutcome({ random: () => 0.699999 }).connected, true);
    assert.equal(createRoundOutcome({ random: () => 0.7 }).connected, false);
});

test('same sender and target always produce success', () => {
    const outcome = createRoundOutcome({
        sender: 'ООО «Эдивеб»',
        target: 'ООО «Эдивеб»',
        random: () => 0.999999,
    });

    assert.equal(outcome.connected, true);
    assert.equal(new Set(outcome.documents.map(({ code }) => code)).size, 1);
});
