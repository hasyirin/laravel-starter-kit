# Autocomplete

`<x-autocomplete>` is a text field that suggests values while the user types. It wraps `flux:autocomplete`. The property gets the text of the field, so the user can also type a value that is not in the list. To store a key, such as an ID, use [`<x-select>`](select.md) instead.

```blade
<x-autocomplete label="State" wire:model="form.state" :options="['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Pahang', 'Perak']" />
```

It takes every [field prop](field.md). When the user picks a suggestion with the mouse, the focus stays in the field. So `.blur` sends the picked value, not the text that the user typed before the pick.

## Options

`options` takes a list of texts, option objects, or enum cases, the same as on [`<x-select>`](select.md#options). A suggestion is the label of its option as text, without HTML. A disabled option stays in the list, but the user cannot pick it. Put `flux:autocomplete.item` elements in the slot to add suggestions after the options.

```blade
<x-autocomplete label="Courier" wire:model="form.courier" :options="['Pos Laju', 'J&T Express', 'DHL']">
    <flux:autocomplete.item>Self-collect</flux:autocomplete.item>
</x-autocomplete>
```

## Suggestions from the Server

For a long list, such as all cities, let the server find the suggestions. Send the text while the user types with `wire:model.live.debounce.250ms`, build the options from it, and turn off the filter in the browser with `:filter="false"`, so the browser shows the suggestions as the server sends them.

```html
<x-autocomplete label="City" wire:model.live.debounce.250ms="city" :options="$this->cities" :filter="false" />
```

```php
public string $city = '';

/**
 * @return list<string>
 */
#[Computed]
public function cities(): array
{
    return City::query()->where('name', 'ilike', "{$this->city}%")->limit(10)->pluck('name')->all();
}
```

## Without a Label

A field in a table row often has no label. Give it an `aria-label`, so screen readers can still name it. With a label, the kit names the field with the text of the label.

```blade
<x-autocomplete wire:model="form.medium" aria-label="Medium" :options="['Newspaper', 'Website', 'Radio']" />
```

## Reference

### Props

| Prop          | Default | Description                                                                                                                 |
|---------------|---------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `options`     | `[]`    | Texts, option objects, or enum cases. See [Options](select.md#options).                                                     |
| `input-class` |         | Classes for the text field.                                                                                                 |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | `flux:autocomplete.item` suggestions after the options.                        |

### Attributes

All other attributes go to `flux:autocomplete`. These include `wire:model` with any modifier, `name`, `placeholder`, `filter`, `size`, `icon`, `clearable`, `disabled`, `readonly`, `invalid`, and `container:class`. `aria-label` names the field in place of the label. `class` goes to the field.
