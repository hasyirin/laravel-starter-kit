// Flux keeps the focus on an element inside its control, so the control never gets a blur event, and wire:model.blur never fires.
// Send one when the focus leaves the control. A click inside the control can move the focus to no element, so the click counts as inside.
export default function blurOnLeave() {
    let pressed = null;

    document.addEventListener(
        'mousedown',
        (event) => {
            pressed = event.target;
            setTimeout(() => (pressed = null));
        },
        true,
    );

    document.addEventListener('focusout', ({ target, relatedTarget }) => {
        const control = target.closest('[data-input-control]');

        if (control && control !== target && !control.contains(relatedTarget ?? pressed)) {
            control.dispatchEvent(new Event('blur'));
        }
    });
}
