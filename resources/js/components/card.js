// Links and controls in the header handle their own clicks, so a click on one of them does not fold the card.
const interactive = 'a, button, input, select, textarea, label, summary, [tabindex], [contenteditable]';

function parts(root) {
    return [...root.querySelectorAll(':scope > [data-flux-card-body], :scope > [data-flux-card-footer]')];
}

// The computed height ignores a transform or zoom on an ancestor, which getBoundingClientRect() includes.
function height(element) {
    return parseFloat(getComputedStyle(element).height);
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

function errorText(root) {
    return parts(root)
        .flatMap((part) => [...part.querySelectorAll('[data-flux-error]')])
        .map((error) => error.textContent.trim())
        .filter(Boolean)
        .join('\n');
}

function hasOpenDialog(root) {
    return parts(root).some((part) => part.querySelector('dialog[open]'));
}

// Livewire's wire:navigate scrolls to the fragment without the browser's reveal of a part that is hidden until found, so a card
// checks the fragment when it starts. Livewire starts the components again after each navigation.
function fragmentTarget(root) {
    const target = location.hash ? document.getElementById(decodeURIComponent(location.hash.slice(1))) : null;

    return target && parts(root).some((part) => part.contains(target)) ? target : null;
}

export default function card() {
    let observer;
    let fold = 0;
    let errors = '';

    return {
        cardOpen: true,
        cardShown: true,

        init() {
            const root = this.$root;

            this.cardOpen = this.cardShown = root.dataset.cardCollapsed !== 'true';
            errors = errorText(root);

            observer = new MutationObserver((records) => {
                const shown = errorText(root);
                const newError = shown !== '' && shown !== errors;
                const dialogOpened = records.some((record) => record.attributeName === 'open') && hasOpenDialog(root);
                const open = root.dataset.cardCollapsed !== 'true';

                errors = shown;

                if (!this.cardOpen && (newError || dialogOpened)) {
                    this.revealCard();
                } else if (records.some((record) => record.target === root && record.attributeName === 'data-card-collapsed') && open !== this.cardOpen) {
                    // Livewire changes the attribute only when the server value changes, so the user's own folds survive other updates.
                    // A card does not fold over an error message or an open dialog.
                    if (open || (shown === '' && !hasOpenDialog(root))) {
                        this.foldCard(open);
                    }
                }
            });
            observer.observe(root, { attributeFilter: ['data-card-collapsed', 'open'], childList: true, characterData: true, subtree: true });

            root.addEventListener('click', (event) => {
                const header = event.target.closest('[data-flux-card-header]');
                const control = event.target.closest(interactive);

                if (header?.parentElement === root && !header.contains(control) && !window.getSelection()?.toString()) {
                    this.toggleCard();
                }
            });

            root.addEventListener(
                'invalid',
                (event) => {
                    if (parts(root).some((part) => part.contains(event.target))) {
                        this.revealCard();
                    }
                },
                true,
            );
            root.addEventListener('beforematch', () => this.revealCard(), true);

            if (!this.cardOpen && (errors !== '' || fragmentTarget(root))) {
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
