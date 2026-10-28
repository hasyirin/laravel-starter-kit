function parts(root) {
    return [...root.querySelectorAll(':scope > [data-flux-card-body], :scope > [data-flux-card-footer]')];
}

function height(element) {
    return element.getBoundingClientRect().height;
}

function cancelFold(root) {
    root.getAnimations()
        .filter((animation) => animation.id === 'card-fold')
        .forEach((animation) => animation.cancel());
}

// Measures the folded card by hiding its parts for a moment, so the header's folded look is part of the result.
function foldedHeight(root) {
    const shown = parts(root).filter((part) => !part.hasAttribute('hidden'));

    shown.forEach((part) => part.setAttribute('hidden', 'until-found'));
    const folded = height(root);
    shown.forEach((part) => part.removeAttribute('hidden'));

    return folded;
}

// An error message or an open dialog must be visible, even in a folded card.
function needsUser(root) {
    return parts(root).some(
        (part) => part.querySelector('dialog[open]') || [...part.querySelectorAll('[data-flux-error]')].some((error) => error.textContent.trim() !== ''),
    );
}

export default function card() {
    let observer;
    let fold = 0;

    return {
        cardOpen: true,
        cardShown: true,

        init() {
            const root = this.$root;

            this.cardOpen = this.cardShown = root.dataset.cardCollapsed !== 'true';

            // Livewire changes the attribute only when the server value changes, so the user's own folds survive other updates.
            observer = new MutationObserver((records) => {
                const open = root.dataset.cardCollapsed !== 'true';

                if (records.some((record) => record.target === root && record.attributeName === 'data-card-collapsed') && open !== this.cardOpen) {
                    this.foldCard(open);
                } else if (!this.cardOpen && needsUser(root)) {
                    this.revealCard();
                }
            });
            observer.observe(root, { attributeFilter: ['data-card-collapsed', 'open'], childList: true, characterData: true, subtree: true });

            root.addEventListener('invalid', () => this.revealCard(), true);
            root.addEventListener('beforematch', () => this.revealCard(), true);

            if (!this.cardOpen && needsUser(root)) {
                this.revealCard();
            }
        },

        destroy() {
            observer.disconnect();
        },

        toggleCard() {
            this.foldCard(!this.cardOpen);
        },

        // Opens at once, so the browser can focus a rejected field or show a match of find in page.
        revealCard() {
            fold++;
            cancelFold(this.$root);
            this.cardOpen = this.cardShown = true;
            parts(this.$root).forEach((part) => part.removeAttribute('hidden'));
        },

        async foldCard(open) {
            const root = this.$root;
            const current = ++fold;
            const from = height(root);

            cancelFold(root);
            this.cardOpen = open;

            if (open) {
                this.cardShown = true;

                // Alpine shows the parts a task later, after the browser paints them at full height, so show them now.
                parts(root).forEach((part) => part.removeAttribute('hidden'));
            } else if (parts(root).some((part) => part.contains(document.activeElement))) {
                root.querySelector(':scope > [data-flux-card-header] [data-card-toggle]').focus();
            }

            const animation = root.animate(
                [
                    { height: `${from}px`, overflow: 'clip' },
                    { height: `${open ? height(root) : foldedHeight(root)}px`, overflow: 'clip' },
                ],
                {
                    id: 'card-fold',
                    duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 250,
                    easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                    fill: 'both',
                },
            );

            await animation.finished.catch(() => {});

            if (current !== fold) {
                return;
            }

            // The last frame holds until Alpine hides the parts, so they do not flash at full height.
            this.cardShown = open;
            await this.$nextTick();
            animation.cancel();
        },
    };
}
