const { Mark, mergeAttributes } = window.FilamentRichEditor.tiptap.core

const allowedSizes = ['12', '14', '16', '18', '20', '24', '28', '32']

const normalizeSize = (size) => {
    const value = String(size ?? '')

    return allowedSizes.includes(value) ? value : null
}

export default Mark.create({
    name: 'fontSize',

    parseHTML() {
        return [
            {
                tag: 'span.font-size[data-font-size]',
            },
        ]
    },

    addAttributes() {
        return {
            'data-font-size': {
                default: null,
                parseHTML: (element) =>
                    normalizeSize(element.getAttribute('data-font-size')),
                renderHTML: (attributes) => {
                    const size = normalizeSize(attributes['data-font-size'])

                    return size ? { 'data-font-size': size } : {}
                },
            },
        }
    },

    renderHTML({ HTMLAttributes }) {
        const size = normalizeSize(HTMLAttributes['data-font-size'])

        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                class: ['font-size', size ? `font-size-${size}` : null]
                    .filter(Boolean)
                    .join(' '),
            }),
            0,
        ]
    },

    addCommands() {
        return {
            setFontSize:
                ({ size }) =>
                ({ commands }) => {
                    const normalizedSize = normalizeSize(size)

                    return normalizedSize
                        ? commands.setMark(this.name, {
                              'data-font-size': normalizedSize,
                          })
                        : false
                },
            unsetFontSize:
                () =>
                ({ commands }) =>
                    commands.unsetMark(this.name),
        }
    },
})
