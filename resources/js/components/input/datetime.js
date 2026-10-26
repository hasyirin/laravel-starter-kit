const pad = (number) => String(number).padStart(2, '0');

function partsAt(instant, timeZone) {
    const format = new Intl.DateTimeFormat('en-US', {
        timeZone,
        hourCycle: 'h23',
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    });

    return Object.fromEntries(format.formatToParts(instant).map(({ type, value }) => [type, Number(value)]));
}

function wallClock(instant, timeZone) {
    const { year, month, day, hour, minute } = partsAt(instant, timeZone);

    return `${year}-${pad(month)}-${pad(day)}T${pad(hour)}:${pad(minute)}`;
}

// The offset can change at a daylight saving switch, so a second pass uses the offset of the first guess.
// In the hour that a switch skips, the two passes disagree: take the later one, as browsers do.
function instantOf(value, timeZone) {
    const [year, month, day, hour = 0, minute = 0] = value.split(/[-T:]/).map(Number);
    const target = Date.UTC(year, month - 1, day, hour, minute);
    const offset = (instant) => {
        const parts = partsAt(instant, timeZone);

        return Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute) - (instant - (instant % 60000));
    };
    const first = target - offset(target);
    const second = target - offset(first);

    return offset(second) === offset(first) ? second : Math.max(first, second);
}

// Livewire sends a Carbon with its offset. A value without one is in the app time zone.
// A string property can hold Laravel's database format, with a space instead of the T.
function parse(value, appTimezone) {
    if (!value) {
        return null;
    }

    const text = value.replace(' ', 'T');

    return /(?:Z|[+-]\d{2}:?\d{2})$/i.test(text) ? Date.parse(text) : instantOf(text, appTimezone);
}

const each = (value, convert) => (value !== null && typeof value === 'object' ? { start: convert(value.start), end: convert(value.end) } : convert(value));

export default function datetimeField() {
    return {
        timezone: null,
        appTimezone: null,

        init() {
            const { timezone, appTimezone, inputModel } = this.$el.dataset;

            this.timezone = timezone;
            this.appTimezone = appTimezone;

            // wire:model sits on a hidden element that holds the value in the app time zone. The picker shows the user's time zone.
            this.picker().addEventListener('input', (event) => this.forward(event, 'input'));
            this.picker().addEventListener('change', (event) => this.forward(event, 'change'));

            this.$nextTick(() => this.show(this.model().value));

            if (inputModel) {
                this.$wire.$watch(inputModel, (value) => this.show(value));
            }
        },

        picker() {
            return this.$root.querySelector('[data-input-control]');
        },

        model() {
            return this.$root.querySelector('[data-input-datetime-model]');
        },

        toPicker(value) {
            return each(value, (part) => {
                const instant = parse(part, this.appTimezone);

                return instant === null ? null : wallClock(instant, this.timezone);
            });
        },

        toModel(value) {
            return each(value, (part) => (part ? wallClock(instantOf(part, this.timezone), this.appTimezone) : null));
        },

        forward(event, type) {
            if (event.target !== this.picker()) {
                return;
            }

            const value = this.toModel(this.picker().value);

            this.model().value = value;
            this.model().dispatchEvent(new CustomEvent(type, { detail: value }));
        },

        show(value) {
            const next = this.toPicker(value);

            if (JSON.stringify(next) !== JSON.stringify(this.picker().value)) {
                this.picker().value = next;
            }
        },
    };
}
