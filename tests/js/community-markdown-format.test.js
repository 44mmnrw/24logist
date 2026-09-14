import test from 'node:test';
import assert from 'node:assert/strict';
import {formatMarkdownSelection} from '../../resources/js/community-markdown-format.js';

test('formats selected text and leaves the editable text selected', () => {
    assert.deepEqual(formatMarkdownSelection('Привет мир', 7, 10, 'bold'), {
        value: 'Привет **мир**',
        selectionStart: 9,
        selectionEnd: 12,
    });
    assert.equal(formatMarkdownSelection('**Текст**', 2, 7, 'bold').value, 'Текст');
});

test('links keep the selected label and select the URL for editing', () => {
    assert.deepEqual(formatMarkdownSelection('Сайт', 0, 4, 'link'), {
        value: '[Сайт](https://)',
        selectionStart: 7,
        selectionEnd: 15,
    });
});

test('list formatting applies to every selected line', () => {
    assert.equal(
        formatMarkdownSelection('Первый\nВторой', 2, 10, 'numbered-list').value,
        '1. Первый\n2. Второй',
    );
});

test('table insertion keeps selected text', () => {
    assert.match(formatMarkdownSelection('Важная строка', 0, 13, 'table').value, /\| Важная строка \| Значение \|/);
});

test('formatting never exceeds the comment limit', () => {
    assert.equal(formatMarkdownSelection('12345', 0, 5, 'bold', 5), null);
});
