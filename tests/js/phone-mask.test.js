import test from 'node:test';
import assert from 'node:assert/strict';
import { initPhoneMask, phoneDigits } from '../../resources/js/phone-mask.js';

class PhoneInput extends EventTarget {
    value = '';
    selectionStart = 0;
    selectionEnd = 0;

    setSelectionRange(start, end = start) {
        this.selectionStart = start;
        this.selectionEnd = end;
    }

    edit(text = '', inputType = 'insertText') {
        const event = new Event('beforeinput', { cancelable: true });
        event.inputType = inputType;
        if (!this.dispatchEvent(event)) return;

        let start = this.selectionStart;
        let end = this.selectionEnd;
        if (start === end && inputType === 'deleteContentBackward') start = Math.max(0, start - 1);
        if (start === end && inputType === 'deleteContentForward') end += 1;
        this.value = this.value.slice(0, start) + text + this.value.slice(end);
        this.setSelectionRange(start + text.length);
        this.dispatchEvent(new Event('input'));
    }
}

const field = (number = '') => {
    const input = new PhoneInput();
    initPhoneMask(input);
    if (number) input.edit(number, 'insertFromPaste');
    return input;
};

test('typing does not add country-code digits to the number', () => {
    const input = field();
    for (const digit of '9991234567') input.edit(digit);
    assert.equal(input.value, '+7 (999) 123-45-67');
});

for (const number of ['+7 (999) 123-45-67', '89991234567', '9991234567']) {
    test(`paste ${number}`, () => assert.equal(field(number).value, '+7 (999) 123-45-67'));
}

test('typing the 8 prefix followed by the number', () => {
    const input = field();
    for (const digit of '89991234567') input.edit(digit);
    assert.equal(input.value, '+7 (999) 123-45-67');
});

test('backspace clears the complete number without getting stuck on separators', () => {
    const input = field('9991234567');
    for (let remaining = 9; remaining >= 0; remaining -= 1) {
        input.edit('', 'deleteContentBackward');
        assert.equal(phoneDigits(input.value).length, remaining);
    }
    assert.equal(input.value, '');
});

test('backspace after the closing bracket removes a digit', () => {
    const input = field('999');
    input.edit('', 'deleteContentBackward');
    assert.equal(input.value, '+7 (99');
});

test('delete skips a separator and removes the next digit', () => {
    const input = field('9991234567');
    const separator = input.value.indexOf('-');
    input.setSelectionRange(separator);
    input.edit('', 'deleteContentForward');
    assert.equal(phoneDigits(input.value), '999123567');
    assert.equal(input.selectionStart, separator);
});

test('replacing digits in the middle preserves the remaining digits and caret', () => {
    const input = field('9991234567');
    const start = input.value.indexOf('23');
    input.setSelectionRange(start, start + 2);
    input.edit('80');
    assert.equal(input.value, '+7 (999) 180-45-67');
    assert.equal(input.selectionStart, start + 2);
});

test('select all then backspace or cut leaves an empty field', () => {
    for (const type of ['deleteContentBackward', 'deleteByCut']) {
        const input = field('9991234567');
        input.setSelectionRange(0, input.value.length);
        input.edit('', type);
        assert.equal(input.value, '');
    }
});

test('extra digits do not shift the number', () => {
    const input = field('9991234567');
    input.edit('8');
    assert.equal(input.value, '+7 (999) 123-45-67');
});

test('incomplete input does not count the country code as a national digit', () => {
    assert.equal(phoneDigits('+7 (999) 123-45-6').length, 9);
    assert.equal(phoneDigits('+7'), '');
});
