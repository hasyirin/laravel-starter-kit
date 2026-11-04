# Entry

`<x-entry>` shows one value that the user reads but does not edit, such as the date that a record was created. A small muted label sits above the value. Use it next to inputs in a form grid, so that read-only values do not look like fields.

```blade
<x-entry label="Created">3 Nov 2026, 10:02 am</x-entry>
```

## Value

The slot is the value. It can hold more than text, such as a status and a link. The parts sit in one row, with a small gap between them.

```blade
<x-entry label="Email verified">
    <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400">
        <span class="size-1.5 rounded-full bg-current"></span>
        Unverified
    </span>

    <flux:link as="button" variant="subtle">Mark as verified</flux:link>
</x-entry>
```

## In a Grid

Put entries in the same grid as the inputs of a form. Each entry takes one cell.

```blade
<div class="grid gap-6 sm:grid-cols-2">
    <x-entry label="Created">3 Nov 2026, 10:02 am</x-entry>
    <x-entry label="Updated">4 Nov 2026, 4:15 pm</x-entry>
</div>
```

## In a Card Footer

To keep read-only values apart from the inputs of a form, put them in the footer of a divided [card](card.md). The card draws a line above the footer. Wrap the entries in one grid element, because Flux puts each child of a footer in its middle column.

```blade
<x-card heading="Details" body="divided" body-class="grid gap-6 sm:grid-cols-2">
    <x-input label="Name" wire:model="form.name" />

    <x-slot:footer>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-entry label="Created">3 Nov 2026, 10:02 am</x-entry>
            <x-entry label="Updated">4 Nov 2026, 4:15 pm</x-entry>
        </div>
    </x-slot:footer>
</x-card>
```

## Screen Readers

The entry is a description list: `<dt>` holds the label, and `<dd>` holds the value. So screen readers read the label with its value. It does not use `flux:label`, because Flux hides a label from screen readers.

## Reference

### Props

| Prop    | Default | Description                  |
|---------|---------|------------------------------|
| `label` |         | Required. The label above the value. |

### Slots

| Slot    | Description |
|---------|-------------|
| Default | The value.  |

### Attributes

All other attributes go to the list element, such as `class`.
