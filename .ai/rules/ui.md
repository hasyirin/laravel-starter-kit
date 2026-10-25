---
paths:
  - 'resources/views/**'
  - 'resources/js/**'
---

# UI

## Use the kit's components before Flux
Build views with the components in `resources/views/components` first, for example `<x-input>` instead of `<flux:input>`. See `docs/components/README.md` for the list. Use a Flux component directly only when no kit component covers the case.

## Keep links and buttons out of Flux labels
Flux hides `flux:label` from screen readers and uses its whole text as the name of the input. So never put a link or a button inside a label, also not in its `trailing` slot. Use the `hint` or `actions` slot of `<x-input>` instead.

## Put the behavior of a component in resources/js/components
Write the Alpine behavior of a component as a function in `resources/js/components/<name>.js`. Register it with `Alpine.data()` in `resources/js/app.js`, before `Livewire.start()`. Keep `wire:model` on the real input, so that all of its modifiers still work.

## Document each component
When you add or change a component in `resources/views/components`, add or update its page in `docs/components/`. The page shows what the component is for and gives an example for every prop. It ends with a Reference section for the props, slots, and attributes. List each page in `docs/components/README.md`.

The local page at `/docs/components` renders each `blade` example as a live preview. So write each example as complete Blade that renders on its own, and bind its inputs to `form.<field>`.
