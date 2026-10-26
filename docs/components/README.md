# Components

The kit's Blade components live in `resources/views/components`. They wrap Flux components and add what the app needs in every form, such as the required mark, help text, and error messages.

Use a kit component first. Use a Flux component directly only when no kit component covers the case.

| Component                                 | Use it for                                                                                                        |
|-------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| [`<x-field>`](field.md)                   | The label row, help text, and error message that every input shares. It also wraps a control that has no kit input. |
| [`<x-input>`](input.md)                   | A text field with a prefix or suffix, a size, case control, and a default value                                   |
| [`<x-input.number>`](input.number.md)     | A number with thousands separators and a fixed number of decimals                                                 |
| [`<x-input.password>`](input.password.md) | A password with a button that shows it                                                                            |
| [`<x-input.date>`](input.date.md)         | A date that the user types or picks from a calendar, or a range of dates                                          |
| [`<x-input.time>`](input.time.md)         | A time that the user types or picks from a list                                                                   |
| [`<x-input.datetime>`](input.datetime.md) | A date and a time in one field                                                                                    |
| [`<x-input.phone>`](input.phone.md)       | A phone number with a country menu. The property gets the E.164 format.                                           |

## Live Docs

In the local environment, open `/docs/components`. The page shows these files and turns each `blade` example into a working preview. Below each preview, it shows the values that the `wire:model` bindings hold, then the code with a copy button. Other environments answer 404.

## Write a Component Page

- Name the file after the component, for example `input.md` for `<x-input>` and `input.number.md` for `<x-input.number>`. Start it with a `#` heading.
- Show every prop of the component in an example. For the field props, link to [Field](field.md) instead of repeating them. End the page with a Reference section for the props, slots, and attributes.
- Write each `blade` example so that it renders on its own, and bind it to `form.<field>`. The live docs page renders all examples on one Livewire page that has a `form` array. A test renders every page, so a broken example fails the tests.
- When you add or change a component, update its page and its row in this table.
