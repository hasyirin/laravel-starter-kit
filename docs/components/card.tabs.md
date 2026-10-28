# Card Tabs

`<x-card.tabs>` puts a [tab](tabs.md) bar in a thin card strip, and the content of the current tab below the strip. The content brings its own cards. The first card of the content joins the strip, so the bar and the card look like one block.

```blade
<livewire:pages.local.examples.tabs query-as="card" bar="card" />
```

It takes the variables of the `InteractsWithTabs` trait, like the other bars and `<x-tabs.content>` together:

```html
<x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" />
```

## Defaults

The bar in the strip is a segmented [Flux tabs](https://fluxui.dev/components/tabs) bar, not the underline of `<x-tabs>`.

## Looks

`variant` picks the bar: `segmented`, `navbar`, or `underline`. `strip` puts the bar in the card strip. Four looks fit the kit. The first one above is the default.

`variant="navbar"` puts a navbar in the strip:

```blade
<livewire:pages.local.examples.tabs query-as="card-navbar" bar="card" variant="navbar" />
```

`:strip="false"` renders the segmented bar alone above the content, with a gap:

```blade
<livewire:pages.local.examples.tabs query-as="card-bare" bar="card" :strip="false" />
```

`variant="underline"` with `:strip="false"` renders the underline bar above the content:

```blade
<livewire:pages.local.examples.tabs query-as="card-underline" bar="card" variant="underline" :strip="false" />
```

Other combinations work too, but the kit does not use them.

## The Card Below the Strip

With a strip, the first card of the content loses its round top corners, so it joins the strip. This works for a card in two places:

- the card is the first element of the content of a view tab
- the card is the root element of the Livewire component of the tab, or its first element

A card inside another element does not join the strip. Give it `class="rounded-t-none"` yourself:

```html
<div class="grid gap-6">
    <x-card class="rounded-t-none">
        <x-input label="Name" wire:model="name" />
    </x-card>
</div>
```

Without a strip, the content needs nothing.

## Title and Description

The card does not show the `title()` or the `description()` of a tab. Put them in the content, or above the card tabs. See [Tabs](tabs.md#title-and-description).

## Reference

### Props

| Prop          | Default      | Description                                                                    |
|---------------|--------------|--------------------------------------------------------------------------------|
| `tabs`        |              | Required. The tabs, `$tabs` from the trait.                                    |
| `current-tab` |              | The current tab, `$currentTab` from the trait.                                 |
| `panels`      |              | Required. The tabs to render, `$tabPanels` from the trait.                     |
| `query-as`    | `tab`        | The URL key, `$queryTabAs` from the trait.                                     |
| `variant`     | `segmented`  | The bar: `segmented`, `navbar`, or `underline`.                                |
| `strip`       | `true`       | Puts the bar in a thin card strip that the first card of the content joins.    |
| `orientation` | `horizontal` | The direction of the slide of the content: `horizontal` or `vertical`.         |
| `params`      | `[]`         | Props for the content of every tab. They win over the tab props.               |
| `aria-label`  |              | The name of the bar for screen readers.                                        |

### Attributes

All other attributes go to the element around the bar and the content, such as `class` and `id`.
