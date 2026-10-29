# Components

The kit's Blade components live in `resources/views/components`. They wrap Flux components and add what the app needs in every form, such as the required mark, help text, and error messages.

Use a kit component first. Use a Flux component directly only when no kit component covers the case.

| Component                                 | Use it for                                                                                                        |
|-------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| [`<x-act>`](act.md)                       | Who did an action on a record, such as who prepared it, with their position then and the time                     |
| [`<x-card>`](card.md)                     | A Flux card built from slots, which the user can fold. `<x-card.group>` joins cards into one block.               |
| [`<x-card.tabs>`](card.tabs.md)           | A tab bar in a thin card strip, with the content of the current tab below it                                      |
| [`<x-field>`](field.md)                   | The label row, help text, and error message that every input shares. It also wraps a control that has no kit input. |
| [`<x-input>`](input.md)                   | A text field with a prefix or suffix, a size, case control, and a default value                                   |
| [`<x-input.number>`](input.number.md)     | A number with thousands separators and a fixed number of decimals                                                 |
| [`<x-input.password>`](input.password.md) | A password with a button that shows it                                                                            |
| [`<x-input.date>`](input.date.md)         | A date that the user types or picks from a calendar, or a range of dates                                          |
| [`<x-input.time>`](input.time.md)         | A time that the user types or picks from a list                                                                   |
| [`<x-input.datetime>`](input.datetime.md) | A date and a time in one field                                                                                    |
| [`<x-input.phone>`](input.phone.md)       | A phone number with a country menu. The property gets the E.164 format.                                           |
| [`<x-textarea>`](textarea.md)             | Text with more than one line                                                                                      |
| [`<x-select>`](select.md)                 | One option from a list with a search input, or many with `multiple`                                               |
| [`<x-pillbox>`](pillbox.md)               | Many options, each shown as a pill                                                                                |
| [`<x-checkbox>`](checkbox.md)             | One checkbox, or a group of checkboxes when it has options                                                        |
| [`<x-radio>`](radio.md)                   | One option from a short list that stays in view                                                                   |
| [`<x-switch>`](switch.md)                 | A setting that is on or off                                                                                       |
| [`<x-slider>`](slider.md)                 | A number, or a range of two numbers, picked with a handle                                                         |
| [`<x-editor>`](editor.md)                 | Rich text with headings, lists, and links. The property gets HTML.                                                |
| [`<x-autocomplete>`](autocomplete.md)     | A text field that suggests values while the user types                                                            |
| [`<x-file-upload>`](file-upload.md)       | Files that the page stores with a model through Media Library                                                     |
| [`<x-tabs>`](tabs.md)                     | A set of tabs in a Livewire component. A switch swaps only the content, and the URL keeps the tab.                |

## Live Docs

In the local environment, open `/docs/components`. The page shows these files and turns each `blade` example into a working preview. Below each preview, it shows the values that the `wire:model` bindings hold, then the code with a copy button. Other environments answer 404.

## Write a Component Page

- Name the file after the component, for example `input.md` for `<x-input>` and `input.number.md` for `<x-input.number>`. Start it with a `#` heading.
- If the component changes a Flux default or wraps a Flux prop with its own default, add a Defaults section after the first example.
- Show every prop of the component in an example. For the field props, link to [Field](field.md) instead of repeating them. End the page with a Reference section for the props, slots, and attributes.
- Write each `blade` example so that it renders on its own, and bind it to `form.<field>`. The live docs page renders all examples on one Livewire page that has a `form` array. A test renders every page, so a broken example fails the tests.
- When you add or change a component, update its page and its row in this table.
