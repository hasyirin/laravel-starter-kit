---
paths:
  - 'resources/views/**'
  - 'resources/js/**'
  - 'resources/css/**'
---

# UI

## Use the kit's components before Flux
Build views with the components in `resources/views/components` first, for example `<x-input>` instead of `<flux:input>`. See `docs/components/README.md` for the list. Use a Flux component directly only when no kit component covers the case, and then wrap it in `<x-field>`.

## Build each input on x-field
Build each kit input on `<x-field>`. Then the label row, the help text, and the error look and work the same in every form. Pass the name of the property to `name`, and add `data-input-control` to the control.

A control with its label beside it, such as one checkbox or a switch, uses an inline `flux:field` instead. Keep its asterisk outside the label too.

## Let each input follow a disabled card
Start each kit input with `@aware(['disabled' => false])`, so that it follows `disabled` on the card or card group around it. Then merge it into the attributes of the control in a `@php` block: `$attributes = $attributes->merge(['disabled' => $disabled]);`. An input that takes `disabled` as a prop passes the prop on. Do not use the one-line `@php(...)` in a file that also has a `@php` block, because Blade then reads them as one block.

To disable a form, pass `disabled` once to its card or card group, not to each input. An input's own `disabled` replaces the card's, so include the card's state when an input has its own rule: `:disabled="$disabled || $this->form->organization_id === null"`.

## Keep links, buttons, and marks out of Flux labels
Flux hides `flux:label` from screen readers and uses its whole text as the name of the input. This includes text that has `aria-hidden`. So put only the label text in a label. Put links, buttons, and marks such as the required asterisk outside it, for example in the `hint` or `actions` slot of `<x-field>`.

## Use one spacing scale
Space every screen with these values. Most come from the defaults of Flux and the kit, so do not override them.

| Gap | Value | How |
| --- | --- | --- |
| Card padding | 24px | The default `<x-card>` size |
| Card heading to its subheading | 4px | The default of `<x-card>` |
| Card header to the first input | 24px | The default of `<x-card>` |
| Input to input, and last input to the buttons | 24px | `space-y-6` or `gap-6` |
| Label to input, and input to help or error | 8px | The default of `<x-field>` |
| Button to button | 8px | `gap-2` |
| Card to card | 24px | `space-y-6` or `gap-6` |
| Page heading to its subheading | 8px | The default of `flux:heading` |
| Page section to page section, such as breadcrumbs, heading, form, and tabs | 32px | `space-y-8` on a `div` around the sections, inside `<flux:main container>`. Put modals after that `div`. |

Use another value only when a screen has a reason that you can name. Then give that reason when you hand in the change.

## Put the behavior of a component in resources/js/components
Write the Alpine behavior of a component as a function in `resources/js/components/`, at the path of the component, for example `input/number.js` for `<x-input.number>`. Register it with `Alpine.data()` in `resources/js/app.js`, before `Livewire.start()`.

Keep `wire:model` on an input element, never on a wrapper, so that all of its modifiers work. If the control shows a different value than the property holds, put `wire:model` on a hidden element after the control. Then repeat the events of the control on that element. `<x-input.number>` uses a hidden input for a string. `<x-input.datetime>` uses a hidden `div`, because a range is an object.

## Document each component
When you add or change a component in `resources/views/components`, add or update its page in `docs/components/`. The page shows what the component is for and gives an example for every prop of its own. For the field props, it links to the Field page. It ends with a Reference section for the props, slots, and attributes. List each page in `docs/components/README.md`.

The local page at `/docs/components` renders each `blade` example as a live preview. So write each example as complete Blade that renders on its own, and bind its inputs to `form.<field>`.

## Write comments in the spirit of ASD-STE100
Write each comment by the rules in `.ai/rules/md.md`: short sentences, one idea per sentence, the active voice, and common words. Keep code names exact.

## Return early and never nest ternaries
Check the edge cases first and return early, so the main path stays at the lowest indent. Never nest ternaries, and this includes Alpine expressions in Blade. Use guard clauses, `if` statements, or a lookup object. Choose readable code over short code, and give each step its own line.

## Name booleans without is
Follow the naming rule of `.ai/rules/php.md` in JavaScript, Alpine, and Blade too. Name a boolean variable, parameter, or method with the plain word, such as `ticked(name)` or `selected`, not `isTicked(name)` or `isSelected`. Keep the `is` or `has` prefix for the same cases: the plain word is in use, the code overrides a browser or library name such as `event.isComposing`, or a function tests its argument, such as `isModifiedClick(event)`. If the plain word is in use, choose another plain word, for example `opened(key)` next to the `open` set. In Blade, keep `has` in a check for a part of a component, such as `$hasActions` next to the `$actions` slot. Name an Alpine flag for a shown part with `showing` too, such as `showingRecoveryInput`. In a component that shows only one part, `showing` is enough.

## Name functions short, without repeating the component
Follow the method naming rule of `.ai/rules/php.md` in JavaScript too, with the file or component in place of the class. Keep the component name in names that other files use, such as `numberField` in `Alpine.data()` and the export `nameEditors`. The markup inside an Alpine component sees its data and methods. If a short name could hide an outer component's name, keep the component name in it, such as `cardOpen` in `card.js`.

## Put the buttons of a form at the right end
End a form or a dialog with its buttons at the right end. In a card footer, put them in `<flux:card.actions>`. Elsewhere, wrap them in `<div class="flex justify-end gap-2">`. Put the primary or danger button last, after Cancel or Back. A narrow, centered form, such as an auth page or the two-factor setup dialog, uses full-width buttons instead. A button that starts a task in its section, such as Enable 2FA, stays under the section text.

## Label a plus button with the record name only
A plus icon already says "add". So label a button with a plus icon with only the singular name of its record, such as "User" or "Passkey". Do not write "Add", "New", or "Create" in it. This includes `App\Filament\Actions\CreateAction`, which throws when it has no label. A modal heading has no icon, so it keeps `common/records.add`.
