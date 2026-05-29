import { postJson } from './landing-forms.js';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function initLandingQuiz(root) {
    const raw = root.dataset.quiz;

    if (!raw) {
        return;
    }

    let data;

    try {
        data = JSON.parse(raw);
    } catch {
        return;
    }

    const questions = Array.isArray(data.questions) ? data.questions : [];
    const finish = data.finish ?? {};
    const success = data.success ?? {};
    const labels = data.labels ?? {};
    const totalSteps = questions.length + 1;
    const answers = {};
    let step = 0;

    const render = () => {
        const progress = totalSteps <= 1
            ? 100
            : Math.round((step / (totalSteps - 1)) * 100);

        if (step >= totalSteps) {
            root.innerHTML = `
                <div class="quiz-result">
                    <h3>${escapeHtml(success.title ?? 'Спасибо!')}</h3>
                    <p>${escapeHtml(success.description ?? '')}</p>
                    <a class="btn btn--primary btn--sm" href="#final-cta">Перейти к подключению</a>
                </div>
            `;

            return;
        }

        const stepLabel = step < questions.length
            ? `${labels.step ?? 'Шаг'} ${step + 1} ${labels.of ?? 'из'} ${totalSteps}`
            : `${labels.step ?? 'Шаг'} ${totalSteps} ${labels.of ?? 'из'} ${totalSteps}`;

        let body = '';

        if (step < questions.length) {
            const question = questions[step];
            const options = Array.isArray(question.options) ? question.options : [];
            const selected = answers[question.id] ?? null;

            body = `
                <h3>${escapeHtml(question.title ?? '')}</h3>
                <div class="quiz-options" role="radiogroup" aria-label="${escapeHtml(question.title ?? 'Вопрос')}">
                    ${options.map((option) => `
                        <label class="quiz-option${selected === option.id ? ' is-selected' : ''}">
                            <input
                                type="radio"
                                name="quiz-q-${question.id}"
                                value="${option.id}"
                                ${selected === option.id ? 'checked' : ''}
                            >
                            <span>${escapeHtml(option.title ?? '')}</span>
                        </label>
                    `).join('')}
                </div>
            `;
        } else {
            body = `
                <h3>${escapeHtml(finish.title ?? 'Куда прислать расчёт?')}</h3>
                <p class="quiz-finish__text">${escapeHtml(finish.description ?? '')}</p>
                <form class="quiz-form" novalidate>
                    <label class="quiz-field">
                        <span>Имя</span>
                        <input type="text" name="name" autocomplete="name" required placeholder="Как к вам обращаться">
                    </label>
                    <label class="quiz-field">
                        <span>Телефон</span>
                        <input type="tel" name="phone" autocomplete="tel" required placeholder="+7 (___) ___-__-__">
                    </label>
                    <label class="quiz-field">
                        <span>Email</span>
                        <input type="email" name="email" autocomplete="email" placeholder="name@company.ru">
                    </label>
                </form>
            `;
        }

        const isFinishStep = step === questions.length;
        const nextLabel = isFinishStep
            ? (labels.submit ?? 'Получить расчёт')
            : (labels.next ?? 'Далее');

        root.innerHTML = `
            <div class="quiz-card__head">
                <span>${escapeHtml(stepLabel)}</span>
                <span data-quiz-progress>${progress}%</span>
            </div>
            <div class="quiz-progress" aria-hidden="true">
                <span style="width: ${progress}%"></span>
            </div>
            <div class="quiz-step">${body}</div>
            <div class="quiz-actions">
                <button type="button" class="btn btn--ghost btn--sm" data-quiz-back ${step === 0 ? 'disabled' : ''}>
                    ${escapeHtml(labels.back ?? 'Назад')}
                </button>
                <button type="button" class="btn btn--primary btn--sm" data-quiz-next>
                    ${escapeHtml(nextLabel)}
                </button>
            </div>
            <p class="quiz-error" data-quiz-error hidden></p>
        `;

        bindStepEvents(isFinishStep, questions[step]?.id);
    };

    const showError = (message) => {
        const error = root.querySelector('[data-quiz-error]');

        if (!error) {
            return;
        }

        error.textContent = message;
        error.hidden = !message;
    };

    const bindStepEvents = (isFinishStep, questionId) => {
        const backButton = root.querySelector('[data-quiz-back]');
        const nextButton = root.querySelector('[data-quiz-next]');

        backButton?.addEventListener('click', () => {
            if (step === 0) {
                return;
            }

            showError('');
            step -= 1;
            render();
        });

        nextButton?.addEventListener('click', async () => {
            showError('');

            if (isFinishStep) {
                const form = root.querySelector('.quiz-form');

                if (!form) {
                    return;
                }

                const formData = new FormData(form);
                const name = String(formData.get('name') ?? '').trim();
                const phone = String(formData.get('phone') ?? '').trim();
                const email = String(formData.get('email') ?? '').trim();

                if (name === '' || phone === '') {
                    showError('Укажите имя и телефон, чтобы мы могли связаться с вами.');

                    return;
                }

                if (!data.submitUrl) {
                    showError('Форма не настроена. Обновите страницу или свяжитесь с нами по телефону.');

                    return;
                }

                if (Object.keys(answers).length < questions.length) {
                    showError('Ответьте на все вопросы квиза.');

                    return;
                }

                nextButton.disabled = true;

                try {
                    await postJson(data.submitUrl, {
                        name,
                        phone,
                        email: email || null,
                        answers,
                        website: '',
                    });

                    step = totalSteps;
                    render();
                } catch (error) {
                    showError(error instanceof Error ? error.message : 'Не удалось отправить заявку.');
                    nextButton.disabled = false;
                }

                return;
            }

            if (questionId && !answers[questionId]) {
                showError('Выберите один из вариантов ответа.');

                return;
            }

            step += 1;
            render();
        });

        if (!isFinishStep && questionId) {
            root.querySelectorAll('.quiz-options input[type="radio"]').forEach((input) => {
                input.addEventListener('change', () => {
                    answers[questionId] = Number(input.value);
                    showError('');
                    root.querySelectorAll('.quiz-option').forEach((label) => {
                        label.classList.toggle('is-selected', label.contains(input) && input.checked);
                    });
                });
            });

            root.querySelectorAll('.quiz-option').forEach((label) => {
                label.addEventListener('click', () => {
                    const input = label.querySelector('input[type="radio"]');

                    if (!input) {
                        return;
                    }

                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        if (isFinishStep) {
            const form = root.querySelector('.quiz-form');

            form?.addEventListener('submit', (event) => {
                event.preventDefault();
                nextButton?.click();
            });
        }
    };

    render();
}

document.querySelectorAll('[data-landing-quiz]').forEach(initLandingQuiz);

export {};
