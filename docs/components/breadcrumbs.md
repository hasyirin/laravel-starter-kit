# Breadcrumbs

`<x-breadcrumbs>` shows the path to the current page. It starts at home, ends at the current page, and folds a long path into a dropdown. It wraps [Flux breadcrumbs](https://fluxui.dev/components/breadcrumbs).

```blade
<x-breadcrumbs :items="[
    ['label' => 'Geography', 'href' => '#'],
    ['label' => 'States', 'href' => '#'],
    ['label' => 'Penang'],
]" />
```

Each item has a `label` and an optional `href`. A link navigates with `wire:navigate`. An item without an `href` is plain text. The last item is the current page, so it is never a link, even with an `href`.

## Defaults

The kit adds these to Flux:

- A home icon first, which links to `route('home')`.
- A trail with more than 4 items folds into a dropdown.
- A long trail wraps to the next line on a small screen.

## Conditional Items

A null item is skipped, so a page can build its trail with a condition:

```blade
<x-breadcrumbs :items="[
    ['label' => 'States', 'href' => '#'],
    false ? ['label' => 'Malaysia', 'href' => '#'] : null,
    ['label' => 'Penang'],
]" />
```

## Long Trails

When a trail has more items than `max`, the parent and the current page stay in view, and the other items move to a dropdown. The count does not include home.

```blade
<x-breadcrumbs :items="[
    ['label' => 'Geography', 'href' => '#'],
    ['label' => 'Countries', 'href' => '#'],
    ['label' => 'Malaysia', 'href' => '#'],
    ['label' => 'States', 'href' => '#'],
    ['label' => 'Penang'],
]" />
```

Change the limit with `max`:

```blade
<x-breadcrumbs :max="2" :items="[
    ['label' => 'Geography', 'href' => '#'],
    ['label' => 'States', 'href' => '#'],
    ['label' => 'Penang'],
]" />
```

The limit counts items, not their width. A trail of long labels that is under the limit wraps instead of folding.

## Home

Pass a URL to `home` to send the icon elsewhere, or `false` to leave it out:

```blade
<x-breadcrumbs home="#" :items="[['label' => 'Users']]" />

<x-breadcrumbs :home="false" :items="[['label' => 'Users']]" />
```

## Reference

| Prop | Default | Description |
| --- | --- | --- |
| `items` | `[]` | A list of items, each with a `label` and an optional `href`. Null items are skipped. With no items, nothing renders. |
| `home` | `route('home')` | The URL of the home icon. `false` leaves it out. |
| `max` | `4` | The most items to show before the trail folds. |

Other attributes go on the `nav` element.
