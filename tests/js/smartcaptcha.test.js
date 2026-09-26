import assert from 'node:assert/strict';
import { test } from 'node:test';

let moduleId = 0;

async function setup({ failScript = false } = {}) {
    const widgets = [];
    const scripts = [];
    const executions = [];
    const api = {
        render(container, options) {
            widgets.push({ container, options, token: '', events: {}, destroyed: false });
            return widgets.length - 1;
        },
        subscribe(id, event, callback) { widgets[id].events[event] = callback; },
        getResponse(id) { return widgets[id].token; },
        reset(id) { widgets[id].token = ''; },
        execute(id) {
            executions.push(id);
            queueMicrotask(() => {
                widgets[id].token = `spin-token-${executions.length}`;
                widgets[id].options.callback(widgets[id].token);
            });
        },
        destroy(id) { widgets[id].destroyed = true; },
    };
    globalThis.window = { setTimeout, clearTimeout };
    globalThis.document = {
        createElement: () => ({ remove() { this.removed = true; } }),
        head: {
            append(script) {
                scripts.push(script);
                queueMicrotask(() => {
                    if (failScript) {
                        failScript = false;
                        script.onerror();
                    } else {
                        window.smartCaptcha = api;
                        window.logistruSmartCaptchaLoaded();
                    }
                });
            },
        },
    };
    const { createSmartCaptcha } = await import(`../../resources/js/smartcaptcha.js?test=${++moduleId}`);
    const createForm = ({ hidden = false, invisible = false } = {}) => {
        const status = { hidden: true, textContent: '' };
        const retry = { hidden: true, addEventListener(event, callback) { this[event] = callback; } };
        const container = {};
        const root = {
            dataset: { sitekey: 'test-public-key', invisible: String(invisible) },
            hidden,
            closest() { return this.hidden ? {} : null; },
            querySelector(selector) {
                return {
                    '[data-smartcaptcha-widget]': container,
                    '[data-smartcaptcha-status]': status,
                    '[data-smartcaptcha-retry]': retry,
                }[selector];
            },
        };
        const controller = createSmartCaptcha({ querySelector: () => root });
        return { controller, root, status, retry };
    };
    return { widgets, scripts, executions, createForm, createSmartCaptcha };
}

test('disabled forms do not load the provider or require a token', async () => {
    const { createSmartCaptcha, scripts } = await setup();
    const captcha = createSmartCaptcha({ querySelector: () => null });
    await captcha.load();
    assert.equal(await captcha.getToken(), '');
    captcha.reset();
    assert.equal(scripts.length, 0);
});

test('hidden modal loads on opening and both forms share one SDK download', async () => {
    const { createForm, scripts, widgets } = await setup();
    const modal = createForm({ hidden: true });
    const contact = createForm();
    await modal.controller.load();
    assert.equal(scripts.length, 0);
    modal.root.hidden = false;
    await Promise.all([modal.controller.load(), contact.controller.load(), modal.controller.load()]);
    assert.equal(scripts.length, 1);
    assert.equal(widgets.length, 2);
    assert.equal(widgets[0].options.sitekey, 'test-public-key');
});

test('tokens are independent, expire, and are reset after a submission', async () => {
    const { createForm, widgets } = await setup();
    const first = createForm();
    const second = createForm();
    await Promise.all([first.controller.load(), second.controller.load()]);
    await assert.rejects(first.controller.getToken(), /не робот/);
    widgets[0].token = 'first-token';
    widgets[1].token = 'second-token';
    assert.equal(await first.controller.getToken(), 'first-token');
    first.controller.reset();
    await assert.rejects(first.controller.getToken(), /не робот/);
    assert.equal(await second.controller.getToken(), 'second-token');
    widgets[1].token = '';
    widgets[1].events['token-expired']();
    await assert.rejects(second.controller.getToken(), /не робот/);
    assert.match(second.status.textContent, /устарела/);
});

test('script loading failure exposes a working retry', async () => {
    const { createForm, scripts, widgets } = await setup({ failScript: true });
    const { controller, retry } = createForm();
    await controller.load();
    assert.equal(retry.hidden, false);
    assert.equal(scripts[0].removed, true);
    retry.click();
    await controller.load();
    assert.equal(scripts.length, 2);
    assert.equal(widgets.length, 1);
    assert.equal(retry.hidden, true);
});

test('widget error rejects stale tokens and keeps retry available after form cleanup', async () => {
    const { createForm, widgets } = await setup();
    const { controller, retry } = createForm();
    await controller.load();
    widgets[0].token = 'stale-token';
    widgets[0].events['javascript-error']();
    await assert.rejects(controller.getToken(), /загрузить капчу/);
    controller.reset();
    assert.equal(retry.hidden, false);
    retry.click();
    await controller.load();
    assert.equal(widgets[0].destroyed, true);
    assert.equal(widgets.length, 2);
    widgets[1].token = 'new-token';
    assert.equal(await controller.getToken(), 'new-token');
});

test('invisible captcha executes for every spin and refreshes its token', async () => {
    const { createForm, widgets, executions } = await setup();
    const { controller } = createForm({ invisible: true });
    await controller.load();
    assert.equal(widgets[0].options.invisible, true);
    assert.equal(await controller.getToken(), 'spin-token-1');
    controller.reset();
    assert.equal(await controller.getToken(), 'spin-token-2');
    assert.deepEqual(executions, [0, 0]);
});
