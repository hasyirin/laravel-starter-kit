# Card

`<x-card>` builds a [Flux card](https://fluxui.dev/components/card) from slots. It puts the heading, the subheading, and the actions in the header, the default slot in the body, and the footer below. With `accordion`, the user can fold the card.

```blade
<x-card heading="Profile" subheading="Other users see your name.">
    <x-input label="Display name" wire:model="form.display_name" />
</x-card>
```

All `<flux:card>` props work on the card: `variant`, `body`, `divider`, `size`, and `highlight`.

```blade
<x-card heading="Profile" variant="muted" body="divided" size="sm">
    <x-input label="Display name" wire:model="form.display_name" />
</x-card>
```

## Heading and Subheading

`heading` and `subheading` take text. Use a slot for more, such as a level for screen readers. The attributes of a slot go to its Flux part, so `level="2"` makes the heading an `h2`.

```blade
<x-card>
    <x-slot:heading level="2" size="lg">Billing address</x-slot:heading>
    <x-slot:subheading>Invoices go to this address.</x-slot:subheading>

    <x-input label="Street" wire:model="form.street" />
</x-card>
```

## Actions

The `actions` slot puts buttons at the right end of the header. Use `size="sm"` buttons, so the header keeps its height.

```blade
<x-card heading="Team">
    <x-slot:actions>
        <flux:button size="sm" icon="plus">Invite</flux:button>
    </x-slot:actions>

    <flux:text>Three people can edit this project.</flux:text>
</x-card>
```

## Footer

The `footer` slot sits below the body. Put buttons in a `<flux:card.actions>`, so they line up at the right end.

```blade
<x-card heading="Profile">
    <x-input label="Display name" wire:model="form.display_name" />

    <x-slot:footer>
        <flux:card.actions>
            <flux:button variant="primary">Save</flux:button>
        </flux:card.actions>
    </x-slot:footer>
</x-card>
```

## Body Class

`class` goes to the card. `body-class` goes to the body, for example to put the fields in a grid.

```blade
<x-card heading="Address" body-class="grid gap-6 sm:grid-cols-2">
    <x-input label="City" wire:model="form.city" />
    <x-input label="Postcode" wire:model="form.postcode" />
</x-card>
```

## Accordion

`accordion` lets the user fold the body and the footer. A click anywhere on the header folds or opens the card, except a click on an action. The chevron turns to show the state. The fold takes 250 ms, and it is instant when the system asks for reduced motion.

```blade
<x-card accordion body-class="grid gap-6 sm:grid-cols-2">
    <x-slot:heading level="2">Address</x-slot:heading>

    <x-slot:actions>
        <flux:button size="sm" variant="ghost" icon="map-pin">Find</flux:button>
    </x-slot:actions>

    <x-input label="City" wire:model="form.city" />
    <x-input label="Postcode" wire:model="form.postcode" />

    <x-slot:footer>
        <flux:text>Used for deliveries.</flux:text>
    </x-slot:footer>
</x-card>
```

The fold works with every `body` style of the Flux card. With `body="divided"`, lines split the header, the body, and the footer. The line under the header fades out while the card folds.

```blade
<x-card heading="Billing" accordion body="divided" body-class="grid gap-6 sm:grid-cols-2">
    <x-input label="Company" wire:model="form.billing_company" />
    <x-input label="Tax number" wire:model="form.tax_number" />

    <x-slot:footer>
        <flux:card.actions>
            <flux:button variant="primary">Save</flux:button>
        </flux:card.actions>
    </x-slot:footer>
</x-card>
```

The heading holds the toggle button, which tells screen readers if the card is open. Give the heading a `level` in the heading slot, so that screen readers also list the card with the other headings. Keep buttons and links out of an accordion heading, because a button cannot hold another button. Put them in `actions`.

An accordion card needs a heading to toggle it. When the heading is empty, for example a `null` from the database, the card renders as a plain card.

A folded card opens by itself when something inside needs the user:

- a field that the browser rejects when the form is sent
- an error message from the server
- an open modal
- a match of find in page, or a link to an element inside the card

An accordion card is an Alpine component with its own `x-data`, so it rejects an `x-data` of yours. Put your Alpine state on an element around the card. An `x-ref` inside the card belongs to the card, so code outside the card cannot reach it through `$refs`.

A card inside an accordion card stays a plain card unless it has its own `accordion`. Each accordion card folds only its own parts.

## Collapsed

`collapsed` folds an accordion card in the HTML from the server, so the card does not open and close while the page loads. For example, `:collapsed="$record->exists"` folds the sections of a record that the user already saved.

```blade
<x-card heading="Notes" accordion collapsed>
    <x-input label="Note" wire:model="form.note" />
</x-card>
```

When the server changes `collapsed`, the card folds or opens to match. Other Livewire updates keep the folds that the user made.

## Group

`<x-card.group>` joins cards into one block. The cards share their borders, and only the outer corners are round. Each accordion card in a group folds on its own, so the user can open several at once.

```blade
<x-card.group>
    <x-card heading="Contact" accordion body-class="grid gap-6 sm:grid-cols-2">
        <x-input label="Email" type="email" wire:model="form.email" />
        <x-input label="Phone" wire:model="form.phone" />
    </x-card>

    <x-card heading="Address" accordion collapsed>
        <x-input label="Street" wire:model="form.street" />
    </x-card>
</x-card.group>
```

Put each card directly in the group. A card inside another element does not join the others.

## Form

`form` renders the group as a `<form>`, so one submit sends the fields of all cards. Put the submit button in a card of its own, so that folding a card never hides it. This example submits to `$refresh`. Use your save method instead.

```blade
<x-card.group form wire:submit="$refresh">
    <x-card heading="Company" accordion>
        <x-input label="Name" wire:model="form.company_name" required />
    </x-card>

    <x-card heading="Address" accordion>
        <x-input label="Street" wire:model="form.company_street" />
    </x-card>

    <x-card>
        <x-slot:footer>
            <flux:card.actions>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </flux:card.actions>
        </x-slot:footer>
    </x-card>
</x-card.group>
```

## Flux Internals

The card builds on parts of Flux that the Flux docs do not cover: CSS variables, data attributes, and the way Flux draws a card. A Flux update can change them without notice. The kit uses them in `resources/css/app.css` and `resources/js/components/card.js`. After you upgrade Flux, search for each name in `vendor/livewire/flux/dist/flux.css` and `vendor/livewire/flux/stubs/resources/views/flux`, then check the examples on this page.

| Internal                                                                 | Where the kit uses it                                                              | What breaks when it changes                         |
|--------------------------------------------------------------------------|------------------------------------------------------------------------------------|-----------------------------------------------------|
| `--flux-card-part-py`                                                    | `app.css`: the padding under a folded header                                       | A folded card keeps a gap under its header.         |
| `--flux-card-band-radius`                                                | `app.css`: the bottom corners of a folded header band                              | A folded separated card gets square corners.        |
| `--flux-bleed-top-radius` and `--flux-bleed-bottom-radius`               | `app.css`: content that bleeds to the edge where cards in a group meet             | That content keeps round corners at the seam.       |
| `--flux-card-body-clip-top-radius` and `--flux-card-body-clip-bottom-radius` | `app.css`: the edge of a flush panel where cards in a group meet               | The edge of the panel is cut at the seam.           |
| `data-flux-card`, `data-flux-card-header`, `data-flux-card-body`, `data-flux-card-footer`, and `data-flux-card-actions` | `app.css` and `card.js`: find a card and its parts | Folding, the folded look, and the group stop working. |
| `data-flux-card-body-variant`                                            | `app.css`: inset panels in a group keep their corners                              | Inset panels in a group get square corners.         |
| `data-flux-error`                                                        | `card.js`: a folded card opens when an error message appears in it                 | Server errors stay hidden in folded cards.          |

The kit also relies on how Flux draws a card:

- A divided card draws its divider as the bottom border of the header. With `divider="inset"`, Flux draws it on the `::after` of the header instead. A folded header removes both.
- The faint highlight of a card is the `::after` of the card. A group hides it on every card after the first.
- Flux gives the last header of a card less padding and round bottom corners. Folded parts stay in the DOM, so the kit copies that look for a header with no part shown below it.

## Reference

### Props

| Prop              | Default | Description                                                                                 |
|-------------------|---------|---------------------------------------------------------------------------------------------|
| `heading`         |         | The heading in the header. It is also a slot.                                               |
| `subheading`      |         | Text under the heading. It is also a slot.                                                  |
| `accordion`       | `false` | Lets the user fold the body and the footer. Without a heading, the card stays plain.        |
| `collapsed`       | `false` | Folds an accordion card in the HTML from the server. The card follows when the value changes. |
| `body-class`      |         | Classes for the body.                                                                       |
| Flux card props   |         | `variant`, `body`, `divider`, `size`, and `highlight`. See the [Flux card](https://fluxui.dev/components/card). |

### Slots

| Slot         | Description                                                                                       |
|--------------|---------------------------------------------------------------------------------------------------|
| Default      | The body.                                                                                         |
| `heading`    | The heading. Its attributes go to `<flux:card.heading>`, such as `level`, `size`, and `accent`.   |
| `subheading` | Text under the heading. Its attributes go to `<flux:card.subheading>`.                            |
| `actions`    | Buttons at the right end of the header. Its attributes go to `<flux:card.actions>`.               |
| `footer`     | The footer below the body. Its attributes go to `<flux:card.footer>`.                             |

### Attributes

All other attributes go to the card, such as `class`, `id`, and `wire:key`. An accordion card rejects `x-data`.

### Group

| Prop   | Default | Description                    |
|--------|---------|--------------------------------|
| `form` | `false` | Renders the group as a `<form>`. |

All other attributes go to the group, such as `class` and `wire:submit`.
