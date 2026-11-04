# Card Tabs

`<x-card.tabs>` renders a segmented [tab](tabs.md) bar, and the content of the current tab below the bar. The content brings its own cards. With `strip`, the bar sits in a thin card strip, and the first card of the content joins the strip, so the bar and the card look like one block.

```blade
<livewire:pages.local.examples.tabs query-as="card" bar="card" />
```

It renders a bar and `<x-tabs.content>` in one tag, so it takes the variables of the `InteractsWithTabs` trait for both:

```html
<x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" />
```

## Defaults

The bar is a segmented [Flux tabs](https://fluxui.dev/components/tabs) bar, not the underline of `<x-tabs>`. It sits alone above the content, with a gap, and has no strip.

## Looks

`variant` picks the bar: `segmented`, `navbar`, or `underline`. Other values go to Flux tabs, so `pills` works too. `strip` puts the bar in the card strip. Four looks fit the kit. The first one above is the default.

`strip` puts the segmented bar in the card strip:

```blade
<livewire:pages.local.examples.tabs query-as="card-strip" bar="card" strip />
```

`variant="navbar"` with `strip` puts a navbar in the strip:

```blade
<livewire:pages.local.examples.tabs query-as="card-navbar" bar="card" variant="navbar" strip />
```

`variant="underline"` renders the underline bar above the content:

```blade
<livewire:pages.local.examples.tabs query-as="card-underline" bar="card" variant="underline" />
```

Other combinations work too, but the kit does not use them.

## Content

`orientation` and `params` go to `<x-tabs.content>`. See [Tabs](tabs.md#content).

`orientation="vertical"` makes the content slide in from the top:

```blade
<livewire:pages.local.examples.tabs query-as="card-vertical" bar="card" orientation="vertical" />
```

`params` gives props to the content of every tab. They win over the props of a tab:

```html
<x-card.tabs :$tabs :$currentTab :panels="$tabPanels" :query-as="$queryTabAs" :params="['project' => $project]" />
```

In this example, `params` gives every field a value:

```blade
<livewire:pages.local.examples.tabs query-as="card-params" bar="card" :content-params="['value' => 'Ada']" />
```

## Screen Readers

`aria-label` gives the bar a name for screen readers:

```blade
<livewire:pages.local.examples.tabs query-as="card-labelled" bar="card" aria-label="Profile sections" />
```

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
| `variant`     | `segmented`  | The bar: `segmented`, `navbar`, `underline`, or a Flux tabs variant.           |
| `strip`       | `false`      | Puts the bar in a thin card strip that the first card of the content joins.    |
| `orientation` | `horizontal` | The direction of the slide of the content: `horizontal` or `vertical`.         |
| `params`      | `[]`         | Props for the content of every tab. They win over the tab props.               |
| `aria-label`  |              | The name of the bar for screen readers.                                        |

### Attributes

All other attributes go to the element around the bar and the content, such as `class` and `id`.
