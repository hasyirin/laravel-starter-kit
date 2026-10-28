function markSelected(item, isSelected) {
    if (item.hasAttribute('data-flux-tab')) {
        item.toggleAttribute('data-selected', isSelected);
        item.setAttribute('aria-selected', String(isSelected));
    } else {
        item.toggleAttribute('data-current', isSelected);

        if (isSelected) {
            item.setAttribute('aria-current', 'page');
        } else {
            item.removeAttribute('aria-current');
        }
    }
}

function isModifiedClick(event) {
    return event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey;
}

export default function tabs(key, defaultSlug) {
    return {
        init() {
            const root = this.$root;
            const linkOf = (event) => event.target.closest?.('[data-tab][href]');

            // Other components can change the URL without this bar rendering again.
            const refreshHref = (event) => {
                const item = linkOf(event);

                if (!item) {
                    return;
                }

                const url = new URL(window.location.href);

                if (item.dataset.tab === defaultSlug) {
                    url.searchParams.delete(key);
                } else {
                    url.searchParams.set(key, item.dataset.tab);
                }

                item.href = url.toString();
            };

            ['pointerenter', 'focusin', 'pointerdown'].forEach((type) => root.addEventListener(type, refreshHref, true));

            root.addEventListener('click', (event) => {
                const item = linkOf(event);

                if (!item || isModifiedClick(event)) {
                    return;
                }

                event.preventDefault();

                const slug = item.dataset.tab;

                if (slug === (this.$wire.tab || defaultSlug)) {
                    return;
                }

                root.querySelectorAll('[data-tab]').forEach((other) => markSelected(other, other === item));

                this.$wire.tab = slug === defaultSlug ? '' : slug;
                this.$wire.switchTab(slug);
            });
        },
    };
}
