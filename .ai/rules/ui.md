---
paths:
  - 'resources/views/**'
  - 'resources/js/**'
---

# UI

## Use the kit's components before Flux
Build views with the components in `resources/views/components` first, for example `<x-input>` instead of `<flux:input>`. See `docs/components/README.md` for the list. Use a Flux component directly only when no kit component covers the case, and then wrap it in `<x-field>`.

## Build each input on x-field
Build each kit input on `<x-field>`, so that the label row, the help text, and the error look and work the same in every form. Pass the name of the property to `name`, and add `data-input-control` to the control.

## Keep links, buttons, and marks out of Flux labels
Flux hides `flux:label` from screen readers and uses its whole text as the name of the input, also the text that has `aria-hidden`. So put only the label text in a label. Put links, buttons, and marks such as the required asterisk outside it, for example in the `hint` or `actions` slot of `<x-field>`.

## Put the behavior of a component in resources/js/components
Write the Alpine behavior of a component as a function in `resources/js/components/`, at the path of the component, for example `input/number.js` for `<x-input.number>`. Register it with `Alpine.data()` in `resources/js/app.js`, before `Livewire.start()`.

Keep `wire:model` on an input element, never on a wrapper, so that all of its modifiers work. If the input shows another value than the property holds, as `<x-input.number>` does, put `wire:model` on a hidden input after the shown input, and repeat the events of the shown input on it.

## Document each component
When you add or change a component in `resources/views/components`, add or update its page in `docs/components/`. The page shows what the component is for and gives an example for every prop of its own. For the field props, it links to the Field page. It ends with a Reference section for the props, slots, and attributes. List each page in `docs/components/README.md`.

The local page at `/docs/components` renders each `blade` example as a live preview. So write each example as complete Blade that renders on its own, and bind its inputs to `form.<field>`.
