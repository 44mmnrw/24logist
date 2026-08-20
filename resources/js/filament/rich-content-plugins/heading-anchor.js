const { Extension } = window.FilamentRichEditor.tiptap.core

const normalizeAnchor = (value) => {
    const normalized = String(value ?? '')
        .trim()
        .replace(/^#+/, '')
        .toLowerCase()
        .replace(/[^a-z0-9_-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^[-_]+|[-_]+$/g, '')

    return normalized || null
}

export default Extension.create({
    name: 'headingAnchor',

    addGlobalAttributes() {
        return [
            {
                types: ['heading', 'paragraph'],
                attributes: {
                    id: {
                        default: null,
                        parseHTML: (element) =>
                            normalizeAnchor(element.getAttribute('id')),
                        renderHTML: (attributes) => {
                            const id = normalizeAnchor(attributes.id)

                            return id ? { id } : {}
                        },
                    },
                },
            },
        ]
    },
})
