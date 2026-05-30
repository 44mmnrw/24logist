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
    const recommendation = data.recommendation ?? {};
    const pageLinks = data.links ?? {};
    const pricingLink = pageLinks.pricing ?? '#pricing';
    const finalCtaLink = pageLinks.finalCta ?? '#final-cta';
    const recommendationStep = questions.length;
    const finishStep = questions.length + 1;
    const totalSteps = questions.length + 2;
    const answers = {};
    let step = 0;

    const getRecommendation = () => {
        const firstQuestionId = data.firstQuestionId;

        if (!firstQuestionId) {
            return null;
        }

        const optionId = answers[firstQuestionId];

        if (!optionId) {
            return null;
        }

        const planId = data.optionPlans?.[optionId] ?? data.optionPlans?.[String(optionId)];

        if (!planId) {
            return null;
        }

        return data.plans?.[planId] ?? data.plans?.[String(planId)] ?? null;
    };

    const render = () => {
        const progress = totalSteps <= 1
            ? 100
            : Math.round((step / (totalSteps - 1)) * 100);

        if (step >= totalSteps) {
            root.innerHTML = `
                <div class="quiz-result">
                    <h3>${escapeHtml(success.title ?? 'Спасибо!')}</h3>
                    <p>${escapeHtml(success.description ?? '')}</p>
                    <a class="btn btn--primary btn--sm" href="${escapeHtml(finalCtaLink)}">Перейти к подключению</a>
                </div>
            `;

            return;
        }

        const stepLabel = `${labels.step ?? 'Шаг'} ${step + 1} ${labels.of ?? 'из'} ${totalSteps}`;

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
        } else if (step === recommendationStep) {
            const plan = getRecommendation();

            if (plan) {
                body = `
                    <h3>${escapeHtml(recommendation.title ?? 'Вам подходит тариф')}</h3>
                    <p class="quiz-finish__text">${escapeHtml(recommendation.description ?? '')}</p>
                    <article class="quiz-recommendation${plan.isHighlighted ? ' quiz-recommendation--hit' : ''}">
                        ${plan.tag ? `<span class="quiz-recommendation__tag">${escapeHtml(plan.tag)}</span>` : ''}
                        <h4>${escapeHtml(plan.title ?? '')}</h4>
                        ${plan.subtitle ? `<p class="quiz-recommendation__desc">${escapeHtml(plan.subtitle)}</p>` : ''}
                        ${plan.price ? `<div class="quiz-recommendation__price">${escapeHtml(plan.price)}</div>` : ''}
                        ${Array.isArray(plan.features) && plan.features.length > 0
                            ? `<ul class="quiz-recommendation__features">${plan.features.map((feature) => `<li>${escapeHtml(feature)}</li>`).join('')}</ul>`
                            : ''}
                    </article>
                    <p class="quiz-recommendation__alt"><a href="${escapeHtml(pricingLink)}">Выбрать другой тариф</a></p>
                `;
            } else {
                body = `
                    <h3>${escapeHtml(recommendation.title ?? 'Вам подходит тариф')}</h3>
                    <p class="quiz-finish__text">Посмотрите все тарифы в разделе ниже или оставьте контакты — мы поможем подобрать план.</p>
                    <p class="quiz-recommendation__alt"><a href="${escapeHtml(pricingLink)}" class="btn btn--ghost btn--sm">Смотреть тарифы</a></p>
                `;
            }
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

        const isFinishStep = step === finishStep;
        const nextLabel = isFinishStep
            ? (labels.submit ?? 'Получить расчёт')
            : (labels.next ?? 'Далее');

        const consentHtml = isFinishStep
            ? `<small class="quiz-consent">${escapeHtml(finish.privacyPrefix ?? 'Нажимая кнопку, вы соглашаетесь с')} <a href="${escapeHtml(finish.privacyUrl ?? '/pages/privacy-policy')}">${escapeHtml(finish.privacyLinkText ?? 'политикой конфиденциальности')}</a></small>`
            : '';

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
            ${consentHtml}
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
                const recommendedPlan = getRecommendation();

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
                        recommended_plan_id: recommendedPlan?.id ?? null,
                        recommended_plan_title: recommendedPlan?.title ?? null,
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
