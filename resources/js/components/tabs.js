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

function isSelected(item) {
    return item.getAttribute('aria-selected') === 'true' || item.getAttribute('aria-current') === 'page';
}

function hasPanel(componentRoot, slug) {
    return [...componentRoot.querySelectorAll('[data-tab-panel]')].some(
        (panel) => panel.dataset.tabPanel === slug && panel.closest('[wire\\:id]') === componentRoot,
    );
}

function isModifiedClick(event) {
    return event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey;
}

export default function tabs() {
    return {
        init() {
            const root = this.$root;
            let latestClick = 0;
            const linkOf = (event) => event.target.closest?.('[data-tab][href]');

            // Other components can change the URL without this bar rendering again.
            const refreshHref = (event) => {
                const item = linkOf(event);

                if (!item) {
                    return;
                }

                const { tabsKey: key, tabsDefault: defaultSlug } = root.dataset;
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
                const defaultSlug = root.dataset.tabsDefault;

                if (isSelected(item)) {
                    return;
                }

                const click = ++latestClick;

                root.querySelectorAll('[data-tab]').forEach((other) => markSelected(other, other === item));

                // A rendered panel shows at once. Otherwise the current panel stays until the new one arrives.
                if (hasPanel(this.$wire.$el, slug)) {
                    this.$wire.tab = slug === defaultSlug ? '' : slug;
                }

                // The server answers clicks in order, so the last click wins. A failed request gives the mark back to the current tab.
                this.$wire.switchTab(slug).finally(() => {
                    if (click === latestClick) {
                        const current = this.$wire.tab || root.dataset.tabsDefault;

                        root.querySelectorAll('[data-tab]').forEach((other) => markSelected(other, other.dataset.tab === current));
                    }
                });
            });
        },
    };
}
