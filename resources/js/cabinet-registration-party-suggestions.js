import { postJson } from './landing-forms.js';

export function initCabinetRegistrationPartySuggestions(modal) {
    const form = modal.querySelector('[data-cabinet-auth-form="registration"]');
    const accountNameInput = form?.querySelector('[data-party-account-name]');
    const innInput = form?.querySelector('[data-party-inn]');
    const accountSuggestions = form?.querySelector('[data-party-account-name-suggestions]');
    const innSuggestions = form?.querySelector('[data-party-inn-suggestions]');
    const statusNode = form?.querySelector('[data-party-lookup-status]');
    const endpoint = modal.dataset.partySuggestionsUrl || '';

    if (!form || !accountNameInput || !innInput || !accountSuggestions || !innSuggestions || !statusNode || !endpoint) return;

    let accountTimer = null;
    let innTimer = null;
    let accountRequestId = 0;
    let innRequestId = 0;

    const normalizeInn = (value) => String(value || '').replace(/\D/g, '').slice(0, 12);

    const showStatus = (message = '', state = '') => {
        statusNode.textContent = message;
        statusNode.classList.toggle('is-success', state === 'success');
        statusNode.classList.toggle('is-error', state === 'error');
    };

    const hideSuggestions = (node) => {
        const nodes = node ? [node] : [accountSuggestions, innSuggestions];
        nodes.forEach((suggestionsNode) => {
            suggestionsNode.replaceChildren();
            suggestionsNode.hidden = true;
        });
    };

    const cancelPending = () => {
        if (accountTimer !== null) window.clearTimeout(accountTimer);
        if (innTimer !== null) window.clearTimeout(innTimer);
        accountTimer = null;
        innTimer = null;
        accountRequestId += 1;
        innRequestId += 1;
    };

    const applySuggestion = (suggestion) => {
        const accountName = String(suggestion?.account_name || '').trim();
        const inn = normalizeInn(suggestion?.inn);
        if (!accountName || ![10, 12].includes(inn.length)) return;

        cancelPending();
        accountNameInput.value = accountName;
        innInput.value = inn;
        hideSuggestions();
        showStatus('Компания и ИНН заполнены.', 'success');
    };

    const renderSuggestions = (node, suggestions) => {
        hideSuggestions(node);
        const fragment = document.createDocumentFragment();

        suggestions.forEach((suggestion) => {
            const accountName = String(suggestion?.account_name || '').trim();
            const inn = normalizeInn(suggestion?.inn);
            if (!accountName || ![10, 12].includes(inn.length)) return;

            const button = document.createElement('button');
            const nameNode = document.createElement('span');
            const innNode = document.createElement('span');
            button.type = 'button';
            button.role = 'option';
            button.className = 'cabinet-login-modal__suggestion';
            nameNode.className = 'cabinet-login-modal__suggestion-name';
            innNode.className = 'cabinet-login-modal__suggestion-inn';
            nameNode.textContent = accountName;
            innNode.textContent = `ИНН ${inn}`;
            button.append(nameNode, innNode);
            button.addEventListener('mousedown', (event) => event.preventDefault());
            button.addEventListener('click', () => applySuggestion(suggestion));
            fragment.append(button);
        });

        node.append(fragment);
        node.hidden = node.childElementCount === 0;
    };

    const loadSuggestions = async ({ query, input, node, requestId, isInn }) => {
        showStatus('Ищем организацию в DaData…');

        try {
            const payload = await postJson(endpoint, { query });
            const currentQuery = isInn
                ? normalizeInn(input.value)
                : String(input.value || '').trim();
            const currentRequestId = isInn ? innRequestId : accountRequestId;
            if (requestId !== currentRequestId || currentQuery !== query) return;

            const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];
            if (isInn && [10, 12].includes(query.length) && suggestions.length === 1) {
                applySuggestion(suggestions[0]);
                return;
            }

            renderSuggestions(node, suggestions);
            showStatus(suggestions.length === 0 ? 'Организация не найдена.' : 'Выберите организацию из списка.');
        } catch (error) {
            const currentRequestId = isInn ? innRequestId : accountRequestId;
            if (requestId !== currentRequestId) return;
            hideSuggestions(node);
            showStatus(
                error instanceof Error ? error.message : 'Подсказки организаций временно недоступны.',
                'error',
            );
        }
    };

    accountNameInput.addEventListener('input', () => {
        const query = String(accountNameInput.value || '').trim();
        accountRequestId += 1;
        const requestId = accountRequestId;
        if (accountTimer !== null) window.clearTimeout(accountTimer);
        hideSuggestions(innSuggestions);
        showStatus();

        if (query.length < 2) {
            hideSuggestions(accountSuggestions);
            return;
        }

        accountTimer = window.setTimeout(() => {
            accountTimer = null;
            void loadSuggestions({ query, input: accountNameInput, node: accountSuggestions, requestId, isInn: false });
        }, 300);
    });

    innInput.addEventListener('input', () => {
        const query = normalizeInn(innInput.value);
        innInput.value = query;
        innRequestId += 1;
        const requestId = innRequestId;
        if (innTimer !== null) window.clearTimeout(innTimer);
        hideSuggestions(accountSuggestions);
        showStatus();

        if (query.length < 3) {
            hideSuggestions(innSuggestions);
            return;
        }

        innTimer = window.setTimeout(() => {
            innTimer = null;
            void loadSuggestions({ query, input: innInput, node: innSuggestions, requestId, isInn: true });
        }, 300);
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) hideSuggestions();
    });
}
