import assert from "node:assert/strict";
import test from "node:test";
import { initRegisterCapabilities } from "../../resources/js/cabinet-registration-capabilities.ts";

function setup(selected = []) {
    const inputs = ["cargo_owner", "forwarder", "carrier"].map((value) => Object.assign(new EventTarget(), {
        value, checked: selected.includes(value), disabled: value === "carrier",
    }));
    const form = new EventTarget();
    const group = { querySelectorAll: () => inputs, closest: () => form };
    const root = { querySelectorAll: () => [group] };
    initRegisterCapabilities(root);
    return { inputs, form, root, selected: () => inputs.filter((input) => input.checked).map((input) => input.value) };
}

test("registration excludes both incompatible roles in either selection order", () => {
    const previous = globalThis.window;
    globalThis.window = new EventTarget();
    try {
        for (const role of ["forwarder", "carrier"]) {
            const state = setup([role]);
            const owner = state.inputs[0];
            owner.checked = true;
            owner.dispatchEvent(new Event("change"));
            assert.deepEqual(state.selected(), ["cargo_owner"]);
            const other = state.inputs.find((input) => input.value === role);
            other.checked = true;
            other.dispatchEvent(new Event("change"));
            assert.deepEqual(state.selected(), [role]);
            assert.equal(state.inputs[2].disabled, true, "does not unlock carrier onboarding");
        }
        assert.deepEqual(setup(["forwarder", "carrier"]).selected(), ["forwarder", "carrier"]);
    } finally {
        globalThis.window = previous;
    }
});

test("restored values and form resets cannot restore incompatible selections", async () => {
    const previous = globalThis.window;
    globalThis.window = new EventTarget();
    try {
        const state = setup(["cargo_owner", "forwarder", "carrier"]);
        assert.deepEqual(state.selected(), ["cargo_owner"]);
        state.inputs[1].checked = true;
        window.dispatchEvent(new Event("pageshow"));
        assert.deepEqual(state.selected(), ["cargo_owner"]);
        state.inputs[2].checked = true;
        state.form.dispatchEvent(new Event("reset"));
        await Promise.resolve();
        assert.deepEqual(state.selected(), ["cargo_owner"]);
        state.inputs[0].checked = false;
        state.inputs[0].dispatchEvent(new Event("change"));
        assert.deepEqual(state.selected(), []);
    } finally {
        globalThis.window = previous;
    }
});
