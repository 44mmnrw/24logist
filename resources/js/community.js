const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const photoPreviewUrls = new WeakMap();
let avatarPreviewUrl = null;
let richEditorModule = null;

const toastContainer = document.querySelector('[data-community-toasts]');
const toastTimers = new WeakMap();

const removeCommunityToast = (toast) => {
    if (!toast?.isConnected || toast.classList.contains('is-leaving')) return;
    window.clearTimeout(toastTimers.get(toast));
    toast.classList.add('is-leaving');
    window.setTimeout(() => toast.remove(), 180);
};

const scheduleCommunityToast = (toast) => {
    window.clearTimeout(toastTimers.get(toast));
    const timeout = Number(toast.dataset.timeout || 6000);
    if (timeout > 0) toastTimers.set(toast, window.setTimeout(() => removeCommunityToast(toast), timeout));
};

const mountCommunityToast = (toast) => {
    if (toast.dataset.mounted) return;
    toast.dataset.mounted = 'true';
    toast.querySelector('[data-community-toast-close]')?.addEventListener('click', () => removeCommunityToast(toast));
    toast.addEventListener('mouseenter', () => window.clearTimeout(toastTimers.get(toast)));
    toast.addEventListener('mouseleave', () => scheduleCommunityToast(toast));
    toast.addEventListener('focusin', () => window.clearTimeout(toastTimers.get(toast)));
    toast.addEventListener('focusout', () => scheduleCommunityToast(toast));
    scheduleCommunityToast(toast);
};

const showCommunityToast = (message, type = 'success', title = null) => {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = `community-toast community-toast--${type}`;
    toast.dataset.communityToast = '';
    toast.dataset.timeout = type === 'danger' ? '8000' : '5000';
    toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');

    const icon = document.createElement('span');
    icon.className = 'community-toast__icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = type === 'danger' ? '!' : '✓';
    const content = document.createElement('div');
    content.className = 'community-toast__content';
    const heading = document.createElement('strong');
    heading.textContent = title || (type === 'danger' ? 'Ошибка' : 'Готово');
    const body = document.createElement('p');
    body.textContent = message;
    content.append(heading, body);
    const close = document.createElement('button');
    close.className = 'community-toast__close';
    close.type = 'button';
    close.dataset.communityToastClose = '';
    close.setAttribute('aria-label', 'Закрыть');
    close.textContent = '×';
    toast.append(icon, content, close);

    const visibleToasts = toastContainer.querySelectorAll('[data-community-toast]:not(.is-leaving)');
    if (visibleToasts.length >= 4) removeCommunityToast(visibleToasts[0]);
    toastContainer.append(toast);
    mountCommunityToast(toast);
};

window.communityNotify = showCommunityToast;
toastContainer?.querySelectorAll('[data-community-toast]').forEach(mountCommunityToast);

const communityTooltip = document.querySelector('[data-community-tooltip]');
let communityTooltipTarget = null;

const prepareCommunityTooltips = (root = document) => {
    root.querySelectorAll?.('.community-page [title]').forEach((element) => {
        element.dataset.communityTooltipText = element.getAttribute('title');
        element.removeAttribute('title');
    });
};

const hideCommunityTooltip = () => {
    if (!communityTooltip || communityTooltip.hidden) return;
    communityTooltip.hidden = true;
    communityTooltipTarget?.removeAttribute('aria-describedby');
    communityTooltipTarget = null;
};

const showCommunityTooltip = (target) => {
    if (!communityTooltip || !target?.dataset.communityTooltipText) return;
    communityTooltipTarget?.removeAttribute('aria-describedby');
    communityTooltipTarget = target;
    communityTooltip.textContent = target.dataset.communityTooltipText;
    communityTooltip.hidden = false;
    target.setAttribute('aria-describedby', communityTooltip.id);

    const targetRect = target.getBoundingClientRect();
    const tooltipRect = communityTooltip.getBoundingClientRect();
    const left = Math.min(
        window.innerWidth - tooltipRect.width - 8,
        Math.max(8, targetRect.left + (targetRect.width - tooltipRect.width) / 2),
    );
    const preferredTop = targetRect.top - tooltipRect.height - 8;
    const top = preferredTop >= 8 ? preferredTop : targetRect.bottom + 8;
    communityTooltip.style.left = `${left}px`;
    communityTooltip.style.top = `${top}px`;
};

prepareCommunityTooltips();

document.addEventListener('pointerover', (event) => {
    const target = event.target.closest?.('[data-community-tooltip-text]');
    if (target) showCommunityTooltip(target);
});

document.addEventListener('pointerout', (event) => {
    if (!communityTooltipTarget || communityTooltipTarget.contains(event.relatedTarget)) return;
    hideCommunityTooltip();
});

document.addEventListener('focusin', (event) => {
    const target = event.target.closest?.('[data-community-tooltip-text]');
    if (target) showCommunityTooltip(target);
});

document.addEventListener('focusout', (event) => {
    if (communityTooltipTarget?.contains(event.relatedTarget)) return;
    hideCommunityTooltip();
});

window.addEventListener('scroll', hideCommunityTooltip, true);
window.addEventListener('resize', hideCommunityTooltip);

const communitySearch = document.querySelector('[data-community-search]');

if (communitySearch) {
    const input = communitySearch.querySelector('input[name="q"]');
    const clear = communitySearch.querySelector('[data-community-search-clear]');
    const hasActiveSearch = input.defaultValue.trim() !== '';
    const updateClearVisibility = () => { clear.hidden = input.value === '' && !hasActiveSearch; };

    input.addEventListener('input', updateClearVisibility);
    clear.addEventListener('click', () => {
        input.value = '';
        updateClearVisibility();
        if (hasActiveSearch) {
            window.location.assign(clear.dataset.resetUrl);
        } else {
            input.focus();
        }
    });
    updateClearVisibility();
}

const sortDropdowns = [...document.querySelectorAll('[data-community-sort] details')];

if (sortDropdowns.length) {
    document.addEventListener('click', (event) => {
        sortDropdowns.forEach((dropdown) => {
            if (dropdown.open && !dropdown.contains(event.target)) dropdown.open = false;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        sortDropdowns.forEach((dropdown) => {
            if (!dropdown.open) return;
            dropdown.open = false;
            dropdown.querySelector('summary')?.focus();
        });
    });
}

if (document.querySelector('[data-rich-editor]')) {
    import('./community-rich-editor.js').then((module) => {
        richEditorModule = module;
        module.mountVisibleCommunityRichEditors();
    });
    document.addEventListener('toggle', (event) => {
        if (event.target.matches('details[open]')) richEditorModule?.mountVisibleCommunityRichEditors();
    }, true);
}

document.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-thread-toggle]');
    if (!button) return;

    const children = document.getElementById(button.getAttribute('aria-controls'));
    if (!children || !button.closest('.community-comment')?.contains(children)) return;

    const collapsed = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!collapsed));
    button.setAttribute('aria-label', collapsed ? 'Развернуть ветку' : 'Свернуть ветку');
    children.classList.toggle('is-collapsed', collapsed);
    Array.from(children.children).forEach((child) => {
        if (child.matches('.community-comment')) child.hidden = collapsed;
    });
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
        richEditorModule?.resetCommunityRichEditor(form);
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
        showCommunityToast('Не удалось сохранить голос. Обновите страницу и попробуйте ещё раз.', 'danger');
    } finally {
        button.disabled = false;
    }
});

const closeReactionPickers = (except = null) => {
    document.querySelectorAll('details.community-reactions[open]').forEach((details) => {
        if (details === except) return;
        details.open = false;
    });
};

document.addEventListener('click', (event) => {
    if (!event.target.closest('.community-reactions')) closeReactionPickers();
});

document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!details.matches?.('details.community-reactions') || !details.open) return;
    closeReactionPickers(details);
}, true);

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const details = document.querySelector('details.community-reactions[open]');
    if (!details) return;
    const toggle = details.querySelector('[data-reaction-toggle]');
    details.open = false;
    toggle?.focus();
});

const closeAwardMenus = (except = null) => {
    document.querySelectorAll('details.community-award-menu[open]').forEach((details) => {
        if (details !== except) details.open = false;
    });
};

document.addEventListener('click', (event) => {
    if (!event.target.closest('.community-award-menu')) closeAwardMenus();
});

document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!details.matches?.('details.community-award-menu') || !details.open) return;
    closeAwardMenus(details);
}, true);

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const details = document.querySelector('details.community-award-menu[open]');
    if (!details) return;
    details.open = false;
    details.querySelector('summary')?.focus();
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
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Не удалось сохранить реакцию.');
        const selected = Array.isArray(result.selected) ? result.selected : (result.selected ? [result.selected] : []);
        buttons.forEach((item) => {
            const active = selected.includes(item.dataset.code);
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-checked', String(active));
            item.querySelector('[data-reaction-count]').textContent = result.reactions[item.dataset.code] || 0;
        });

        const selectedButtons = selected
            .map((code) => [...buttons].find((item) => item.dataset.code === code))
            .filter(Boolean);
        const trigger = widget.querySelector('[data-reaction-toggle]');
        const triggerIcons = trigger?.querySelector('[data-reaction-trigger-icons]');

        trigger?.classList.toggle('is-active', selectedButtons.length > 0);
        if (triggerIcons) {
            const reactionChips = (selectedButtons.length ? selectedButtons : [null]).map((item) => {
                const chip = document.createElement('span');
                chip.className = `community-reaction-chip${item ? '' : ' community-reaction-chip--empty'}`;
                const icon = document.createElement('span');
                icon.className = 'community-reaction__emoji';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = item?.dataset.emoji || '👍';
                chip.append(icon);
                if (item) {
                    const label = document.createElement('span');
                    label.className = 'community-reaction-chip__label';
                    label.textContent = item.dataset.label;
                    chip.append(label);
                }
                return chip;
            });
            triggerIcons.replaceChildren(...reactionChips);
        }
    } catch (error) {
        const message = error.message || 'Не удалось сохранить реакцию. Обновите страницу и попробуйте ещё раз.';
        closeReactionPickers();
        showCommunityToast(message, 'danger', message.includes('не более трёх') ? 'Лимит реакций' : null);
    } finally {
        buttons.forEach((item) => { item.disabled = false; });
    }
});

const copyCommunityText = async (text) => {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch (_) {
            // Firefox can reject Clipboard API despite HTTPS. Fall back to execCommand.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.readOnly = true;
    textarea.setAttribute('aria-hidden', 'true');
    textarea.style.position = 'fixed';
    textarea.style.inset = '0 auto auto -9999px';
    textarea.style.opacity = '0';
    document.body.append(textarea);
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    const copied = document.execCommand('copy');
    textarea.remove();

    if (!copied) throw new Error('Copy command was rejected');
};

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-url]');
    if (!button) return;

    const url = button.dataset.shareUrl;
    const label = button.querySelector('[data-share-label]');

    try {
        await copyCommunityText(url);
        showCommunityToast('Ссылка на тему скопирована в буфер обмена.');
        if (label) {
            const previous = label.textContent;
            label.textContent = 'Ссылка скопирована';
            window.setTimeout(() => { label.textContent = previous; }, 1800);
        }
    } catch (_) {
        showCommunityToast('Не удалось скопировать ссылку. Скопируйте адрес из строки браузера.', 'danger');
    }
});

const reportDialog = document.querySelector('[data-report-dialog]');

const awardDialog = document.querySelector('[data-award-dialog]');

const commentDeleteDialog = document.querySelector('[data-comment-delete-dialog]');
let pendingCommentDeleteForm = null;

const closeCommentDeleteDialog = () => {
    pendingCommentDeleteForm = null;
    if (!commentDeleteDialog) return;
    if (typeof commentDeleteDialog.close === 'function') commentDeleteDialog.close();
    else commentDeleteDialog.removeAttribute('open');
};

document.addEventListener('submit', (event) => {
    const form = event.target.closest?.('[data-comment-delete-form]');
    if (!form || form.dataset.commentDeleteConfirmed) return;
    event.preventDefault();
    pendingCommentDeleteForm = form;
    if (!commentDeleteDialog?.open) {
        if (typeof commentDeleteDialog?.showModal === 'function') commentDeleteDialog.showModal();
        else commentDeleteDialog?.setAttribute('open', '');
    }
});

if (commentDeleteDialog) {
    commentDeleteDialog.addEventListener('click', (event) => {
        if (event.target === commentDeleteDialog || event.target.closest('[data-comment-delete-cancel]')) {
            closeCommentDeleteDialog();
            return;
        }

        if (!event.target.closest('[data-comment-delete-confirm]') || !pendingCommentDeleteForm) return;
        const form = pendingCommentDeleteForm;
        form.dataset.commentDeleteConfirmed = 'true';
        if (typeof commentDeleteDialog.close === 'function') commentDeleteDialog.close();
        else commentDeleteDialog.removeAttribute('open');
        form.requestSubmit();
    });
    commentDeleteDialog.addEventListener('cancel', () => {
        pendingCommentDeleteForm = null;
    });
}

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
