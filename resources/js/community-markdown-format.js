const WRAPPERS = {
    bold: ['**', '**', 'жирный текст'],
    italic: ['*', '*', 'курсив'],
    strike: ['~~', '~~', 'зачёркнутый текст'],
    code: ['`', '`', 'код'],
};

export function formatMarkdownSelection(value, start, end, command, maxLength = Infinity) {
    const selected = value.slice(start, end);
    let replaceStart = start;
    let replaceEnd = end;
    let replacement;
    let selectionStart;
    let selectionEnd;

    if (WRAPPERS[command]) {
        const [opening, closing, placeholder] = WRAPPERS[command];
        const wrappedSelection = selected.startsWith(opening) && selected.endsWith(closing)
            && selected.length > opening.length + closing.length;
        const wrappedAroundSelection = value.slice(start - opening.length, start) === opening
            && value.slice(end, end + closing.length) === closing;
        if (wrappedAroundSelection) {
            replaceStart = start - opening.length;
            replaceEnd = end + closing.length;
            replacement = selected;
            selectionStart = replaceStart;
            selectionEnd = replaceStart + selected.length;
        } else if (wrappedSelection) {
            replacement = selected.slice(opening.length, -closing.length);
            selectionStart = start;
            selectionEnd = start + replacement.length;
        } else {
            const content = selected || placeholder;
            replacement = opening + content + closing;
            selectionStart = start + opening.length;
            selectionEnd = selectionStart + content.length;
        }
    } else if (command === 'link') {
        const label = selected || 'текст ссылки';
        replacement = `[${label}](https://)`;
        selectionStart = selected ? start + label.length + 3 : start + 1;
        selectionEnd = selectionStart + (selected ? 'https://'.length : label.length);
    } else if (['heading', 'list', 'numbered-list', 'quote'].includes(command)) {
        replaceStart = start === 0 ? 0 : value.lastIndexOf('\n', start - 1) + 1;
        const nextLine = value.indexOf('\n', end);
        replaceEnd = nextLine === -1 ? value.length : nextLine;
        const content = value.slice(replaceStart, replaceEnd)
            || (command === 'heading' ? 'Заголовок' : command === 'quote' ? 'Цитата' : 'Пункт списка');
        const lines = content.split('\n');
        replacement = lines.map((line, index) => {
            const prefix = command === 'heading' ? '### ' : command === 'quote' ? '> ' : command === 'list' ? '- ' : `${index + 1}. `;
            return prefix + line;
        }).join('\n');
        selectionStart = replaceStart;
        selectionEnd = replaceStart + replacement.length;
    } else if (command === 'code-block' || command === 'table') {
        const content = command === 'code-block'
            ? `\`\`\`\n${selected || 'код'}\n\`\`\``
            : `| Столбец 1 | Столбец 2 |\n| --- | --- |\n| ${selected.replace(/\|/g, '\\|').replace(/\n/g, ' ') || 'Значение'} | Значение |`;
        const before = start > 0 && value[start - 1] !== '\n' ? '\n\n' : '';
        const after = end < value.length && value[end] !== '\n' ? '\n\n' : '';
        replacement = before + content + after;
        selectionStart = start + before.length;
        selectionEnd = selectionStart + content.length;
    } else {
        return null;
    }

    if (value.length - (replaceEnd - replaceStart) + replacement.length > maxLength) return null;

    return {
        value: value.slice(0, replaceStart) + replacement + value.slice(replaceEnd),
        selectionStart,
        selectionEnd,
    };
}
