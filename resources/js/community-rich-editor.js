import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Markdown } from '@tiptap/markdown';
import { TableKit } from '@tiptap/extension-table';
import Placeholder from '@tiptap/extension-placeholder';

const editors = new WeakMap();

function syncBody(container) {
    const editor = editors.get(container);
    const textarea = container.querySelector('textarea[name="body_markdown"]');
    if (!editor || !textarea) return;
    textarea.value = editor.getMarkdown();
    const error = container.querySelector('[data-rich-editor-error]');
    if (error) error.hidden = textarea.value.length <= 5000;
}

function updateToolbar(container) {
    const editor = editors.get(container);
    if (!editor) return;
    const active = {
        bold: editor.isActive('bold'), italic: editor.isActive('italic'),
        strike: editor.isActive('strike'), heading: editor.isActive('heading'),
        link: editor.isActive('link'), list: editor.isActive('bulletList'),
        'numbered-list': editor.isActive('orderedList'), quote: editor.isActive('blockquote'),
        code: editor.isActive('code'), 'code-block': editor.isActive('codeBlock'),
        table: editor.isActive('table'),
    };
    container.querySelectorAll('[data-rich-format]').forEach((button) => {
        button.setAttribute('aria-pressed', String(Boolean(active[button.dataset.richFormat])));
    });
}

function mount(container) {
    if (editors.has(container)) return;
    const textarea = container.querySelector('textarea[name="body_markdown"]');
    const surface = container.querySelector('[data-rich-editor-surface]');
    const toolbar = container.querySelector('.community-rich-toolbar');
    if (!textarea || !surface || !toolbar) return;

    const editor = new Editor({
        element: surface,
        extensions: [
            StarterKit.configure({ link: { openOnClick: false } }),
            TableKit,
            Placeholder.configure({ placeholder: textarea.placeholder || 'Введите комментарий…' }),
            Markdown,
        ],
        content: textarea.value,
        contentType: 'markdown',
        editorProps: { attributes: { 'aria-label': textarea.getAttribute('aria-label') || textarea.placeholder || 'Комментарий' } },
        onUpdate: () => syncBody(container),
        onSelectionUpdate: () => updateToolbar(container),
        onTransaction: () => updateToolbar(container),
    });
    editors.set(container, editor);
    const error = document.createElement('small');
    error.dataset.richEditorError = '';
    error.className = 'community-rich-editor__error';
    error.setAttribute('role', 'alert');
    error.textContent = 'Комментарий не может быть длиннее 5 000 символов.';
    error.hidden = true;
    surface.after(error);
    textarea.hidden = true;
    surface.hidden = false;
    toolbar.hidden = false;
    syncBody(container);
    updateToolbar(container);
}

export function mountVisibleCommunityRichEditors() {
    document.querySelectorAll('[data-rich-editor]').forEach((container) => {
        if (!container.closest('details:not([open])')) mount(container);
    });
}

export function resetCommunityRichEditor(form) {
    const container = form.querySelector('[data-rich-editor]');
    const editor = container && editors.get(container);
    const textarea = container?.querySelector('textarea[name="body_markdown"]');
    if (!editor || !textarea) return;
    editor.commands.setContent(textarea.defaultValue || '', { contentType: 'markdown' });
    syncBody(container);
}

document.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-rich-format]');
    if (!button) return;
    const container = button.closest('[data-rich-editor]');
    const editor = editors.get(container);
    if (!editor) return;
    const chain = editor.chain().focus();
    switch (button.dataset.richFormat) {
        case 'bold': chain.toggleBold().run(); break;
        case 'italic': chain.toggleItalic().run(); break;
        case 'strike': chain.toggleStrike().run(); break;
        case 'heading': chain.toggleHeading({ level: 3 }).run(); break;
        case 'list': chain.toggleBulletList().run(); break;
        case 'numbered-list': chain.toggleOrderedList().run(); break;
        case 'quote': chain.toggleBlockquote().run(); break;
        case 'code': chain.toggleCode().run(); break;
        case 'code-block': chain.toggleCodeBlock().run(); break;
        case 'table': chain.insertTable({ rows: 3, cols: 2, withHeaderRow: true }).run(); break;
        case 'link': {
            const href = window.prompt('Адрес ссылки', editor.getAttributes('link').href || 'https://');
            if (href === null) break;
            if (!href.trim()) chain.unsetLink().run();
            else if (/^https?:\/\//i.test(href.trim())) chain.setLink({ href: href.trim() }).run();
            else window.communityNotify?.('Укажите ссылку, начинающуюся с https:// или http://', 'danger');
            break;
        }
        default: break;
    }
    syncBody(container);
    updateToolbar(container);
});

document.addEventListener('submit', (event) => {
    const container = event.target.querySelector?.('[data-rich-editor]');
    if (!container || !editors.has(container)) return;
    syncBody(container);
    const textarea = container.querySelector('textarea[name="body_markdown"]');
    if (textarea.value.length > 5000) {
        event.preventDefault();
        editorFocus(container);
    }
}, true);

function editorFocus(container) {
    editors.get(container)?.commands.focus();
}
