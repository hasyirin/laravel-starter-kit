const cases = {
    upper: (value) => value.toUpperCase(),
    lower: (value) => value.toLowerCase(),
    title: (value) => value.toLowerCase().replace(/(^|[\s-])(\p{L})/gu, (match, before, letter) => before + letter.toUpperCase()),
    sentence: (value) => value.toLowerCase().replace(/(^\s*|[.!?]\s+)(\p{L})/gu, (match, before, letter) => before + letter.toUpperCase()),
};

export default function inputField() {
    return {
        value: '',
        previous: '',
        defaultValue: null,

        get matchesDefault() {
            return this.defaultValue !== null && this.value === this.defaultValue;
        },

        init() {
            const { inputCase, inputDefault, inputModel } = this.$el.dataset;
            const toCase = cases[inputCase];

            if (toCase) {
                // Capture runs before wire:model and x-model read the value, so they store the changed case.
                this.$el.addEventListener('input', (event) => this.changeCase(event, toCase), true);
                this.$el.addEventListener('compositionend', (event) => this.changeCase(event, toCase), true);
            }

            if (inputDefault === undefined) {
                return;
            }

            this.defaultValue = toCase ? toCase(inputDefault) : inputDefault;

            this.$el.addEventListener('input', (event) => {
                if (event.target === this.control()) {
                    this.value = event.target.value;
                }
            });

            this.$nextTick(() => (this.value = this.control()?.value ?? ''));

            if (inputModel) {
                this.$wire.$watch(inputModel, (value) => (this.value = value === null || value === undefined ? '' : String(value)));
            }
        },

        control() {
            return this.$root.querySelector('[data-input-control]');
        },

        changeCase(event, toCase) {
            const control = this.control();

            if (event.target !== control || event.isComposing) {
                return;
            }

            const next = toCase(control.value);

            if (next === control.value) {
                return;
            }

            const start = control.selectionStart;
            const end = control.selectionEnd;

            control.value = next;

            if (start !== null) {
                control.setSelectionRange(start, end);
            }

            if (event.type === 'compositionend') {
                control.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },

        useDefault(checked) {
            if (checked && !this.matchesDefault) {
                this.previous = this.value;
            }

            this.write(checked ? this.defaultValue : this.previous === this.defaultValue ? '' : this.previous);
        },

        write(value) {
            const control = this.control();

            control.value = value;
            this.value = value;

            // Fire the events of a user who types and leaves the field, so every wire:model modifier picks it up.
            control.dispatchEvent(new Event('input', { bubbles: true }));
            control.dispatchEvent(new Event('change', { bubbles: true }));
            control.dispatchEvent(new Event('blur'));
        },
    };
}
