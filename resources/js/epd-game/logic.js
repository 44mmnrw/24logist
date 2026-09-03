export const DOCUMENTS = Object.freeze([
    Object.freeze({ code: 'ЭТрН', label: 'накладная' }),
    Object.freeze({ code: 'ЭЗЗ', label: 'заказ-заявка' }),
    Object.freeze({ code: 'ЭПЭ', label: 'поручение' }),
]);

export const randomFloat = () => {
    if (globalThis.crypto?.getRandomValues) {
        const value = new Uint32Array(1);
        globalThis.crypto.getRandomValues(value);

        return value[0] / 4294967296;
    }

    return Math.random();
};

export const randomItem = (items, random = randomFloat) => (
    items[Math.floor(random() * items.length)]
);

export const shuffledItems = (items, random = randomFloat) => {
    const shuffled = [...items];

    for (let index = shuffled.length - 1; index > 0; index -= 1) {
        const swapIndex = Math.floor(random() * (index + 1));
        [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
    }

    return shuffled;
};

export const canStartRound = ({ sender, target, phase }) => (
    Boolean(sender && target) && phase !== 'spinning'
);

export const getSelectionPhase = (sender, target) => (
    sender && target ? 'ready' : 'idle'
);

export const createRoundOutcome = ({
    random = randomFloat,
    successRate = 0.45,
    reelCount = 3,
    sender = '',
    target = '',
} = {}) => {
    const connected = (sender !== '' && sender === target) || random() < successRate;

    if (connected) {
        const documentType = randomItem(DOCUMENTS, random);

        return {
            connected,
            documents: Array.from({ length: reelCount }, () => documentType),
        };
    }

    return {
        connected,
        documents: shuffledItems(DOCUMENTS, random).slice(0, reelCount),
    };
};
