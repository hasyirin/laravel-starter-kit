const browsers = [
    { pattern: /Edg|Edge/, name: 'Edge' },
    { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
    { pattern: /Firefox|FxiOS/, name: 'Firefox' },
    { pattern: /Chrome|CriOS/, name: 'Chrome' },
    { pattern: /Safari/, name: 'Safari' },
];

const systems = [
    { pattern: /iPhone/, name: 'iPhone' },
    { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
    { pattern: /Android/, name: 'Android' },
    { pattern: /Mac/, name: 'Mac' },
    { pattern: /Windows/, name: 'Windows' },
];

function match(patterns, userAgent) {
    return patterns.find(({ pattern }) => pattern.test(userAgent))?.name;
}

function defaultName() {
    const userAgent = navigator.userAgent;

    return [match(browsers, userAgent), match(systems, userAgent)].filter(Boolean).join(' on ');
}

export default function passkeyModal() {
    return {
        supported: false,
        name: '',
        loading: false,
        error: null,

        init() {
            this.name = defaultName();
            this.checkSupport();
        },

        checkSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },

        async register() {
            if (!this.name.trim()) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                await this.$wire.registered();
            } catch (error) {
                if (error.name !== 'UserCancelledError') {
                    this.error = error.message;
                }
            } finally {
                this.loading = false;
            }
        },
    };
}
