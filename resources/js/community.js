import {formatMarkdownSelection} from './community-markdown-format.js';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const photoPreviewUrls = new WeakMap();
let avatarPreviewUrl = null;

function setMarkdownPreview(editor, visible) {
    if (!editor) return;
    const textarea = editor.querySelector('textarea[name="body_markdown"]');
    const preview = editor.querySelector('[data-markdown-preview]');
    const toggle = editor.querySelector('[data-markdown-preview-toggle]');
    if (!textarea || !preview || !toggle) return;
    textarea.hidden = visible;
    preview.hidden = !visible;
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.textContent = visible ? 'Редактировать Markdown' : 'Предпросмотр';
}

function applyMarkdownFormat(editor, command) {
    const textarea = editor.querySelector('textarea[name="body_markdown"]');
    if (!textarea) return;
    const result = formatMarkdownSelection(
        textarea.value, textarea.selectionStart, textarea.selectionEnd, command,
        Number(textarea.maxLength) > 0 ? textarea.maxLength : Infinity,
    );
    if (!result) {
        textarea.focus();
        textarea.setCustomValidity('Достигнут лимит символов комментария.');
        textarea.reportValidity();
        textarea.setCustomValidity('');
        return;
    }
    setMarkdownPreview(editor, false);
    textarea.value = result.value;
    textarea.focus();
    textarea.setSelectionRange(result.selectionStart, result.selectionEnd);
    textarea.dispatchEvent(new Event('input', {bubbles: true}));
}

document.addEventListener('click', async (event) => {
    const formatButton = event.target.closest?.('[data-markdown-format]');
    if (formatButton) {
        const editor = formatButton.closest('[data-markdown-editor]');
        if (editor) applyMarkdownFormat(editor, formatButton.dataset.markdownFormat);
        return;
    }

    const toggle = event.target.closest?.('[data-markdown-preview-toggle]');
    if (!toggle) return;
    const editor = toggle.closest('[data-markdown-editor]');
    if (!editor) return;
    if (toggle.getAttribute('aria-pressed') === 'true') {
        setMarkdownPreview(editor, false);
        editor.querySelector('textarea[name="body_markdown"]')?.focus();
        return;
    }

    const textarea = editor.querySelector('textarea[name="body_markdown"]');
    const preview = editor.querySelector('[data-markdown-preview]');
    if (!textarea || !preview) return;
    if (!textarea.value.trim()) {
        preview.textContent = 'Добавьте текст для предпросмотра.';
        setMarkdownPreview(editor, true);
        return;
    }

    toggle.disabled = true;
    const requestedText = textarea.value;
    try {
        const response = await fetch(editor.dataset.markdownPreviewUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({body_markdown: requestedText}),
        });
        if (!response.ok) throw new Error('preview_failed');
        const {html} = await response.json();
        if (textarea.value !== requestedText || !editor.isConnected) return;
        preview.innerHTML = html || '';
        setMarkdownPreview(editor, true);
    } catch (_) {
        if (textarea.value !== requestedText || !editor.isConnected) return;
        preview.textContent = 'Не удалось показать предпросмотр. Попробуйте ещё раз.';
        setMarkdownPreview(editor, true);
    } finally {
        toggle.disabled = false;
    }
});

document.addEventListener('keydown', (event) => {
    const textarea = event.target.closest?.('[data-markdown-editor] textarea[name="body_markdown"]');
    if (!textarea || !(event.ctrlKey || event.metaKey) || event.altKey) return;
    const command = {b: 'bold', i: 'italic', k: 'link'}[event.key.toLowerCase()];
    if (!command) return;
    event.preventDefault();
    applyMarkdownFormat(textarea.closest('[data-markdown-editor]'), command);
});

document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-community-avatar-input]');
    if (!input) return;

    const preview = input.closest('.community-avatar-setting')?.querySelector('[data-community-avatar-preview]');
    const feedback = input.closest('.community-avatar-setting')?.querySelector('[data-community-avatar-feedback]');
    const file = input.files?.[0];
    if (!preview || !feedback) return;

    if (avatarPreviewUrl) URL.revokeObjectURL(avatarPreviewUrl);
    avatarPreviewUrl = null;
    preview.hidden = true;
    preview.removeAttribute('src');
    feedback.textContent = '';
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size < 1 || file.size > 8 * 1024 * 1024) {
        input.value = '';
        feedback.textContent = 'Выберите JPG, PNG или WebP размером до 8 МБ.';
        return;
    }

    avatarPreviewUrl = URL.createObjectURL(file);
    preview.src = avatarPreviewUrl;
    preview.hidden = false;
    feedback.textContent = `Выбрано: ${file.name}. Нажмите «Сохранить».`;
    const remove = input.form?.querySelector('[data-community-avatar-remove]');
    if (remove) remove.checked = false;
});

document.addEventListener('change', (event) => {
    if (!event.target.matches?.('[data-community-avatar-remove]') || !event.target.checked) return;
    const input = event.target.form?.querySelector('[data-community-avatar-input]');
    if (!input) return;
    input.value = '';
    input.dispatchEvent(new Event('change', {bubbles: true}));
});

document.addEventListener('dragenter', (event) => {
    const zone = event.target.closest?.('[data-community-dropzone]');
    if (!zone || !Array.from(event.dataTransfer?.types || []).includes('Files')) return;
    event.preventDefault();
    zone.classList.add('is-dragover');
});

document.addEventListener('dragover', (event) => {
    const zone = event.target.closest?.('[data-community-dropzone]');
    if (!zone || !Array.from(event.dataTransfer?.types || []).includes('Files')) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'copy';
    zone.classList.add('is-dragover');
});

document.addEventListener('dragleave', (event) => {
    const zone = event.target.closest?.('[data-community-dropzone]');
    if (zone && (!event.relatedTarget || !zone.contains(event.relatedTarget))) zone.classList.remove('is-dragover');
});

document.addEventListener('drop', (event) => {
    const zone = event.target.closest?.('[data-community-dropzone]');
    if (!zone) return;
    event.preventDefault();
    zone.classList.remove('is-dragover');

    const input = zone.querySelector('input[type="file"]');
    const files = Array.from(event.dataTransfer?.files || []);
    if (!input || !files.length) return;

    try {
        const transfer = new DataTransfer();
        (input.multiple ? files : files.slice(0, 1)).forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        input.dispatchEvent(new Event('change', {bubbles: true}));
    } catch (_) {
        try {
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        } catch (_) {
            // The file picker remains available in browsers without file-drop support.
        }
    }
});

document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-community-photo-input]')
        || (event.target.matches?.('input[name="remove_photos[]"]')
            ? event.target.form?.querySelector('[data-community-photo-input]')
            : null);
    if (!input) return;

    const preview = input.closest('.community-photo-editor')?.querySelector('[data-community-photo-preview]');
    if (!preview) return;

    (photoPreviewUrls.get(input) || []).forEach((url) => URL.revokeObjectURL(url));
    photoPreviewUrls.set(input, []);
    preview.replaceChildren();

    const existing = input.form?.querySelectorAll('input[name="remove_photos[]"]:not(:checked)').length || 0;
    const files = Array.from(input.files || []);
    const composer = input.closest('[data-community-composer]');
    const fileCount = composer?.querySelector('[data-composer-file-count]');
    const maxPhotos = Number(input.dataset.maxPhotos || 3);
    const maxBytes = Number(input.dataset.maxBytes || 2 * 1024 * 1024);
    if (existing + files.length > maxPhotos || files.some((file) => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size < 1 || file.size > maxBytes)) {
        input.value = '';
        preview.textContent = `Можно добавить не больше ${maxPhotos} фото JPG, PNG или WebP, каждое до ${maxBytes / 1024 / 1024} МБ.`;
        if (fileCount) fileCount.hidden = true;
        return;
    }

    const urls = [];
    files.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        urls.push(url);
        const figure = document.createElement('figure');
        const image = document.createElement('img');
        image.src = url;
        image.alt = `Предпросмотр: ${file.name}`;
        const caption = document.createElement('figcaption');
        caption.textContent = file.name;
        figure.append(image, caption);
        if (composer) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'community-comment-composer__remove-photo';
            remove.dataset.composerRemovePhoto = String(index);
            remove.setAttribute('aria-label', `Убрать фото ${file.name}`);
            remove.textContent = '×';
            figure.append(remove);
        }
        preview.append(figure);
    });
    photoPreviewUrls.set(input, urls);
    if (fileCount) {
        fileCount.textContent = `${files.length} фото`;
        fileCount.hidden = files.length === 0;
    }
});

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-composer-photo-trigger]');
    if (trigger) {
        trigger.closest('[data-community-composer]')?.querySelector('[data-community-photo-input]')?.click();
        return;
    }

    const remove = event.target.closest('[data-composer-remove-photo]');
    if (!remove) return;
    const input = remove.closest('[data-community-composer]')?.querySelector('[data-community-photo-input]');
    if (!input) return;
    try {
        const transfer = new DataTransfer();
        Array.from(input.files || []).filter((_, index) => index !== Number(remove.dataset.composerRemovePhoto))
            .forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    } catch (_) {
        input.value = '';
    }
    input.dispatchEvent(new Event('change', {bubbles: true}));
});

document.addEventListener('input', (event) => {
    const textarea = event.target.closest?.('[data-composer-textarea]');
    if (!textarea) return;
    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(textarea.scrollHeight, 220)}px`;
    textarea.style.overflowY = textarea.scrollHeight > 220 ? 'auto' : 'hidden';
});

document.addEventListener('reset', (event) => {
    const form = event.target.closest?.('[data-community-composer]');
    if (!form) return;
    window.setTimeout(() => {
        setMarkdownPreview(form.querySelector('[data-markdown-editor]'), false);
        form.querySelector('[data-community-photo-input]')?.dispatchEvent(new Event('change', {bubbles: true}));
        const textarea = form.querySelector('[data-composer-textarea]');
        textarea.style.height = '';
        textarea.style.overflowY = '';
        const details = form.closest('.community-comment__actions details');
        if (details) details.open = false;
    }, 0);
});

document.addEventListener('paste', (event) => {
    const form = event.target.closest?.('[data-community-composer]');
    if (!form) return;
    const pasted = Array.from(event.clipboardData?.files || []).filter((file) => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type));
    if (!pasted.length) return;
    const input = form.querySelector('[data-community-photo-input]');
    if (!input) return;
    try {
        const transfer = new DataTransfer();
        [...Array.from(input.files || []), ...pasted].forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        event.preventDefault();
        input.dispatchEvent(new Event('change', {bubbles: true}));
    } catch (_) {
        // File selection remains available if the browser cannot transfer clipboard files.
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-vote] button[data-value]');
    if (!button || button.disabled) return;

    const widget = button.closest('[data-vote]');
    const requested = Number(button.dataset.value);
    const value = button.classList.contains('is-active') ? 0 : requested;
    button.disabled = true;

    try {
        const response = await fetch(widget.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({target_type: widget.dataset.type, target_id: Number(widget.dataset.id), value}),
        });
        if (!response.ok) throw new Error('vote_failed');
        const result = await response.json();
        widget.querySelector('[data-score]').textContent = result.score;
        widget.querySelectorAll('button[data-value]').forEach((item) => {
            item.classList.toggle('is-active', Number(item.dataset.value) === result.user_vote);
        });
    } catch (_) {
        window.alert('Не удалось сохранить голос. Обновите страницу и попробуйте ещё раз.');
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-community-reactions] button[data-code]');
    if (!button) return;

    const widget = button.closest('[data-community-reactions]');
    const buttons = widget.querySelectorAll('button[data-code]');
    buttons.forEach((item) => { item.disabled = true; });

    try {
        const response = await fetch(widget.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({target_type: widget.dataset.type, target_id: Number(widget.dataset.id), code: button.dataset.code}),
        });
        if (!response.ok) throw new Error('reaction_failed');
        const result = await response.json();
        buttons.forEach((item) => {
            const active = result.selected === item.dataset.code;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', String(active));
            item.querySelector('[data-reaction-count]').textContent = result.reactions[item.dataset.code] || 0;
        });
    } catch (_) {
        window.alert('Не удалось сохранить реакцию. Обновите страницу и попробуйте ещё раз.');
    } finally {
        buttons.forEach((item) => { item.disabled = false; });
    }
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-url]');
    if (!button) return;

    const url = button.dataset.shareUrl;
    const label = button.querySelector('[data-share-label]');

    try {
        if (navigator.share) {
            await navigator.share({title: document.title, url});
            return;
        }

        await navigator.clipboard.writeText(url);
        if (label) {
            const previous = label.textContent;
            label.textContent = 'Ссылка скопирована';
            window.setTimeout(() => { label.textContent = previous; }, 1800);
        }
    } catch (_) {}
});

const reportDialog = document.querySelector('[data-report-dialog]');

const awardDialog = document.querySelector('[data-award-dialog]');

if (awardDialog) {
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-award-open]');
        if (openButton) {
            const form = awardDialog.querySelector('form');
            form.reset();
            form.querySelector('[data-award-type]').value = openButton.dataset.awardType;
            form.querySelector('[data-award-id]').value = openButton.dataset.awardId;
            awardDialog.querySelector('#community-award-title').textContent = openButton.dataset.awardType === 'comment'
                ? 'Наградить этот комментарий' : 'Наградить эту публикацию';
            awardDialog.querySelector('[data-award-target]').textContent = openButton.dataset.awardTitle;
            awardDialog.querySelector('[data-award-length]').textContent = '0';
            if (typeof awardDialog.showModal === 'function') awardDialog.showModal();
            else awardDialog.setAttribute('open', '');
        } else if (event.target.closest('[data-award-close]')) {
            if (typeof awardDialog.close === 'function') awardDialog.close();
            else awardDialog.removeAttribute('open');
        }
    });

    awardDialog.addEventListener('click', (event) => {
        if (event.target === awardDialog) awardDialog.close();
    });

    awardDialog.querySelector('[data-award-message]').addEventListener('input', (event) => {
        awardDialog.querySelector('[data-award-length]').textContent = String(Array.from(event.target.value).length);
    });
}

if (reportDialog) {
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-report-open]');
        const closeButton = event.target.closest('[data-report-close]');

        if (openButton) {
            const form = reportDialog.querySelector('form');
            form.reset();
            form.querySelector('[data-report-target-type]').value = openButton.dataset.reportType;
            form.querySelector('[data-report-target-id]').value = openButton.dataset.reportId;

            if (typeof reportDialog.showModal === 'function') reportDialog.showModal();
            else reportDialog.setAttribute('open', '');
        } else if (closeButton) {
            if (typeof reportDialog.close === 'function') reportDialog.close();
            else reportDialog.removeAttribute('open');
        }
    });

    reportDialog.addEventListener('click', (event) => {
        if (event.target === reportDialog) reportDialog.close();
    });
}

const maxMiniApp = document.querySelector('[data-max-mini-app]');

if (maxMiniApp) {
    const returnButton = maxMiniApp.querySelector('[data-max-return]');
    const statusNode = maxMiniApp.querySelector('[data-max-mini-status]');
    const initData = window.WebApp?.initData;
    const startParam = window.WebApp?.initDataUnsafe?.start_param;

    const post = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify(payload),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result?.message || 'MAX auth failed');
        return result;
    };

    if (!initData) {
        statusNode.textContent = 'Откройте эту страницу внутри приложения MAX.';
    } else {
        const completeLogin = async () => {
            const hasChallenge = startParam && startParam !== 'community-login';
            try {
                const result = await post(
                    hasChallenge ? maxMiniApp.dataset.approveUrl : maxMiniApp.dataset.sessionUrl,
                    hasChallenge ? {challenge: startParam, init_data: initData} : {init_data: initData},
                );

                returnButton.href = result.return_url;
                returnButton.hidden = false;
                statusNode.textContent = 'Аккаунт MAX подтверждён. Нажмите «Авторизоваться».';
            } catch (_) {
                statusNode.textContent = 'Не удалось подтвердить вход. Откройте ссылку заново.';
            }
        };

        completeLogin();
    }

    returnButton.addEventListener('click', (event) => {
        const platform = window.WebApp?.platform;

        if (platform === 'web' || typeof window.WebApp?.openLink !== 'function') return;

        event.preventDefault();
        window.WebApp.openLink(returnButton.href);
    });
}
