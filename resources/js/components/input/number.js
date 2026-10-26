import inputField from '../input';

// Rounds half away from zero on the digits, like PHP's number_format(), because toFixed() rounds 1.005 down.
function round(whole, fraction, decimals) {
    const negative = whole.startsWith('-');
    const digits = BigInt((negative ? whole.slice(1) : whole) + fraction.slice(0, decimals) || '0') + BigInt(fraction[decimals] >= '5');
    const text = digits.toString().padStart(decimals + 1, '0');

    return [(negative && digits > 0n ? '-' : '') + text.slice(0, text.length - decimals), text.slice(text.length - decimals)];
}

function format(value, { decimals, comma, separator, disableNegatives }) {
    let text = value === null || value === undefined ? '' : String(value);

    if (disableNegatives) {
        text = text.replace(/-/g, '');
    }

    if (!/^-?\d*\.?\d+$|^-?\d+\.?$/.test(text)) {
        return text;
    }

    let [whole, fraction = ''] = text.split('.');

    if (fraction.length > decimals) {
        [whole, fraction] = round(whole, fraction, decimals);
    }

    whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, comma);

    return decimals > 0 ? whole + separator + fraction.padEnd(decimals, '0') : whole;
}

function unformat(value, { comma, separator, disableNegatives }) {
    let text = comma === '' ? value : value.replaceAll(comma, '');

    text = text.replace(separator, '.');

    return disableNegatives ? text.replace(/-/g, '') : text;
}

function sameNumber(left, right) {
    const a = left === null || left === undefined ? '' : String(left);
    const b = right === null || right === undefined ? '' : String(right);

    return a === b || (a !== '' && b !== '' && Number(a) === Number(b));
}

export default function numberField() {
    const field = inputField();
    const initField = field.init;

    return Object.assign(field, {
        number: null,

        init() {
            const { number, inputModel } = this.$el.dataset;

            // JSON, because Blade trims attribute values and a space is a valid thousands separator.
            this.number = JSON.parse(number);

            // wire:model sits on a hidden input that holds the plain number. Each event of the shown input is repeated on it, so every wire:model modifier works.
            this.$el.addEventListener('input', (event) => this.forward(event, new Event('input')));
            this.$el.addEventListener('change', (event) => this.forward(event, new Event('change')));
            this.$el.addEventListener('blur', (event) => this.forward(event, new Event('blur')), true);
            this.$el.addEventListener('keydown', (event) => event.key === 'Enter' && this.forward(event, new KeyboardEvent('keydown', { key: 'Enter' })));

            this.$nextTick(() => this.show(this.model().value));

            if (inputModel) {
                this.$wire.$watch(inputModel, (value) => this.show(value));
            }

            initField.call(this);
        },

        model() {
            return this.$root.querySelector('[data-input-number-model]');
        },

        display(value) {
            return format(value, this.number);
        },

        forward(event, copy) {
            const control = this.control();

            if (event.target !== control) {
                return;
            }

            this.model().value = unformat(control.value, this.number);
            this.model().dispatchEvent(copy);
        },

        show(value) {
            const control = this.control();

            if (!sameNumber(unformat(control.value, this.number), value)) {
                control.value = this.display(value);
            }
        },
    });
}
