export default function sliderField() {
    return {
        value: null,

        get text() {
            return Array.isArray(this.value) ? this.value.join(' – ') : (this.value ?? '');
        },

        init() {
            const control = this.$root.querySelector('[data-input-control]');
            const read = () => (this.value = control.value);
            const model = control.getAttributeNames().find((name) => name.startsWith('wire:model'));

            this.$nextTick(read);
            control.addEventListener('input', read);

            // Flux sends no event when Livewire sets the value, so follow the property instead.
            if (model) {
                this.$watch(`$wire.$get('${control.getAttribute(model)}')`, () => this.$nextTick(read));
            }
        },
    };
}
