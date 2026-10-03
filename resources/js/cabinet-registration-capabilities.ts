const initialized = new WeakSet<HTMLElement>();

export function initRegisterCapabilities(root: ParentNode = document, onChange: () => void = () => {}): void {
    root.querySelectorAll<HTMLElement>("[data-register-capabilities]").forEach((group) => {
        if (initialized.has(group)) return;
        initialized.add(group);

        const inputs = Array.from(group.querySelectorAll<HTMLInputElement>("input[type='checkbox']"));
        const normalize = (changed?: HTMLInputElement): void => {
            const owner = inputs.find((input) => input.value === "cargo_owner");
            if (owner === undefined) return;

            if (changed?.checked && (changed.value === "forwarder" || changed.value === "carrier")) {
                owner.checked = false;
            }
            if (owner.checked) {
                inputs.forEach((input) => {
                    if (input.value === "forwarder" || input.value === "carrier") input.checked = false;
                });
            }
            onChange();
        };

        inputs.forEach((input) => {
            input.addEventListener("change", () => normalize(input));
        });
        group.closest("form")?.addEventListener("reset", () => queueMicrotask(() => normalize()));
        window.addEventListener("pageshow", () => normalize());
        normalize();
    });
}
