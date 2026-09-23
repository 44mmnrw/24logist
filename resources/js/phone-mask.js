const digitsOnly = (value) => String(value ?? '').replace(/\D/g, '');
const hasCountryCode = (value) => String(value ?? '').trim().startsWith('+7')
    || (digitsOnly(value).length !== 10 && /^[78]/.test(digitsOnly(value)));

export const phoneDigits = (value) => {
    const text = String(value ?? '').trim();
    const digits = digitsOnly(text);
    return (hasCountryCode(text) ? digits.slice(1) : digits).slice(0, 10);
};

const formatDigits = (digits) => {
    if (!digits) return '';

    let value = `+7 (${digits.slice(0, 3)}`;
    if (digits.length >= 3) value += ')';
    if (digits.length > 3) value += ` ${digits.slice(3, 6)}`;
    if (digits.length > 6) value += `-${digits.slice(6, 8)}`;
    if (digits.length > 8) value += `-${digits.slice(8, 10)}`;
    return value;
};

const caretAfterDigits = (value, count) => {
    if (!value) return 0;
    if (count === 0) return Math.min(4, value.length);

    for (let index = 4; index < value.length; index += 1) {
        if (/\d/.test(value[index]) && --count === 0) return index + 1;
    }
    return value.length;
};

export const initPhoneMask = (input) => {
    if (!input) return;

    input.addEventListener('input', () => {
        const raw = input.value;
        const caret = input.selectionStart ?? raw.length;
        const digits = phoneDigits(raw);
        const beforeCaret = Math.max(0, digitsOnly(raw.slice(0, caret)).length - Number(hasCountryCode(raw)));
        const formatted = formatDigits(digits) || (/^\+?[78]$/.test(raw.trim()) ? '+7' : '');

        input.value = formatted;
        const position = caret === raw.length ? formatted.length : caretAfterDigits(formatted, beforeCaret);
        input.setSelectionRange(position, position);
    });

    input.addEventListener('beforeinput', (event) => {
        if (!event.cancelable || !event.inputType.startsWith('delete') || !input.value.startsWith('+7')) return;

        const start = input.selectionStart;
        const end = input.selectionEnd;
        if (start === null || end === null) return;
        if (start === end && !['deleteContentBackward', 'deleteContentForward'].includes(event.inputType)) return;

        const positions = [...input.value.matchAll(/\d/g)].map((match) => match.index).filter((index) => index > 1);
        let from = positions.filter((index) => index < start).length;
        let to = positions.filter((index) => index < end).length;

        if (start === end) {
            if (event.inputType === 'deleteContentBackward') from = Math.max(0, from - 1);
            else to = Math.min(positions.length, to + 1);
        }

        event.preventDefault();
        const digits = phoneDigits(input.value);
        input.value = formatDigits(digits.slice(0, from) + digits.slice(to));
        const position = caretAfterDigits(input.value, from);
        input.setSelectionRange(position, position);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
};
