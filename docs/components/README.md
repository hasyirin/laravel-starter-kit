# Components

The kit's Blade components live in `resources/views/components`. They wrap Flux components and add what the app needs in every form, such as the required mark, help text, and error messages.

Use a kit component first. Use a Flux component directly only when no kit component covers the case.

| Component               | Use it for                                                                                                                |
|-------------------------|---------------------------------------------------------------------------------------------------------------------------|
| [`<x-input>`](input.md) | A text field with a label, help text, a hint, actions, a prefix or suffix, a size, case control, and a default value      |

## Live Docs

In the local environment, open `/docs/components`. The page shows these files and turns each `blade` example into a working preview. Below each preview, it shows the values that the `wire:model` bindings hold, then the code with a copy button. Other environments answer 404.

## Write a Component Page

- Name the file after the component, for example `input.md` for `<x-input>`, and start it with a `#` heading.
- Show every prop in an example. End the page with a Reference section for the props, slots, and attributes.
- Write each `blade` example so that it renders on its own, and bind it to `form.<field>`. The live docs page renders all examples on one Livewire page that has a `form` array. A test renders every page, so a broken example fails the tests.
- When you add or change a component, update its page and its row in this table.
