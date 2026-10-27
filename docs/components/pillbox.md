# Pillbox

`<x-pillbox>` lets the user pick many options, and shows each one as a pill. It wraps `flux:pillbox`. The property gets an array.

```blade
<x-pillbox label="Skills" wire:model="form.skills" :options="['php' => 'PHP', 'js' => 'JavaScript', 'go' => 'Go', 'rust' => 'Rust']" placeholder="Choose skills" />
```

It takes every [field prop](field.md). `options` takes the same forms as on [Select](select.md#options), and the slot takes Flux pillbox options. A pill shows the label of its option as text, or its selected label.

## Defaults

The kit changes these Flux defaults:

| Prop         | Kit default              | Flux default    |
|--------------|--------------------------|-----------------|
| `multiple`   | Always on                | Off, one value  |
| `searchable` | `true` for the `default` | `false`         |

## Search

The search input filters the options in the browser. `:searchable="false"` removes it.

```blade
<x-pillbox label="Colors" wire:model="form.colors" :options="['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue']" :searchable="false" placeholder="Choose colors" />
```

`search-model` and `search-placeholder` let the server find the options, the same way as on [Select](select.md#search-on-the-server).

```blade
<x-pillbox label="Tags" wire:model="form.tags" :options="['urgent' => 'Urgent', 'later' => 'Later']" search-model="form.tag_search" search-placeholder="Search tags" placeholder="Choose tags" />
```

## Combobox

`variant="combobox"` puts the input in the pillbox, so the user types right after the pills to filter the options.

```blade
<x-pillbox label="Languages" wire:model="form.languages" variant="combobox" :options="['ms' => 'Malay', 'en' => 'English', 'zh' => 'Chinese', 'ta' => 'Tamil']" placeholder="Type a language" />
```

## Reference

### Props

| Prop                 | Default                  | Description                                                                                                                 |
|----------------------|--------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props          |                          | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `options`            | `[]`                     | The options. See [Select](select.md#options).                                                                               |
| `variant`            | `default`                | `default`, or `combobox` for an input in the pillbox.                                                                       |
| `searchable`         | `true` for the `default` | Adds the search input. Only the `default` variant has one.                                                                  |
| `search-model`       |                          | The property that gets the search text. The browser then stops filtering the options. Works with the `default` variant only. |
| `search-placeholder` | "Search..."              | The placeholder of the search input. Works with `search-model`.                                                             |
| `input-class`        |                          | Classes for the pillbox.                                                                                                    |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | Flux pillbox options, after the options of `options`.                          |

### Attributes

`wire:model` goes to `flux:pillbox`. Flux keeps the focus on an element inside the pillbox, so the kit sends the blur event when the focus leaves the pillbox. Every modifier works, also `.blur` and `.live.blur`.

All other attributes go to `flux:pillbox`, so its props work: `placeholder`, `size`, `clearable`, `disabled`, `invalid`, `empty`, and `search:placeholder`. `class` goes to the field.
