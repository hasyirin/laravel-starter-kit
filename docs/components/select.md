# Select

`<x-select>` lets the user pick an option from a list. It wraps `flux:select`, and the list has a search input.

```blade
<x-select label="Country" wire:model="form.country" :options="['MY' => 'Malaysia', 'SG' => 'Singapore', 'BN' => 'Brunei', 'ID' => 'Indonesia']" placeholder="Choose a country" />
```

It takes every [field prop](field.md).

## Defaults

The kit changes these Flux defaults:

| Prop         | Kit default                    | Flux default                    |
|--------------|--------------------------------|---------------------------------|
| `variant`    | `listbox`                      | `default`, the browser's select |
| `searchable` | `true` for the `listbox`       | `false`                         |

## Options

`options` takes a list in one of these forms. One list can mix them.

| Form                   | Example                                                     |
|------------------------|-------------------------------------------------------------|
| A label for each value | `['MY' => 'Malaysia']`, or the `options()` of a kit enum    |
| An enum case           | `[Status::Draft, Status::Published]`                        |
| An option object       | `[Option::make('pro')->label('Pro')->icon('star')]`         |

An enum case takes its label, icon, and color from its enum, when the enum has `HasLabel`, `HasIcon`, or `HasColor`. Without a label, an option shows its value.

`<x-pillbox>`, `<x-checkbox>`, and `<x-radio>` take the same forms.

### Option Objects

`Option::make()` takes the value: a string, a number, or a backed enum. Use an option object when an option needs more than a label.

```blade
@use(App\Livewire\Components\Option)

<x-select label="Plan" wire:model="form.plan" placeholder="Choose a plan" :options="[
    Option::make('free')->label('Free')->icon('gift')->description('One project'),
    Option::make('pro')->label('Pro')->icon('star')->description('Unlimited projects')->badge('Popular')->color('green'),
    Option::make('team')->label('Team')->icon('user-group')->selectedLabel('Team plan'),
    Option::make('legacy')->label('Legacy')->disabled(),
]" />
```

| Method            | Description                                                                               |
|-------------------|-------------------------------------------------------------------------------------------|
| `label()`         | The text of the option: plain text, or HTML as an `Illuminate\Contracts\Support\Htmlable`. |
| `selectedLabel()` | The text on the select when the option is picked. Without it, the select shows the label, icon, and badge. |
| `description()`   | Text under the label. It shows in the list only.                                          |
| `icon()`          | A Flux icon name, or HTML such as an SVG.                                                 |
| `badge()`         | A badge after the label.                                                                  |
| `color()`         | The color of the badge, such as `green`.                                                  |
| `disabled()`      | The user cannot pick the option.                                                          |
| `hidden()`        | Leaves the option out of the list. `visible()` does the opposite, and the last call wins. |

Each method except `color()` also takes a closure, which runs when the list renders. Use it for a value that depends on the request, such as a permission:

```php
Option::make('admin')->label('Administrator')->disabled(fn (): bool => ! auth()->user()->can('assign-admin'))
```

Each component shows the parts of an option that its look has room for:

| Component                                   | Shows                                                                           |
|---------------------------------------------|---------------------------------------------------------------------------------|
| `<x-select>`, `variant="combobox"` included | Everything. The select itself shows the label, icon, and badge, or the selected label. |
| `<x-select variant="default">`              | The label only, as text.                                                        |
| `<x-pillbox>`                               | Everything in the list. A pill shows the label as text, or the selected label.  |
| `<x-checkbox>` and `<x-radio>`              | The label. The default and `cards` variants also show the description. `cards` and `buttons` show the icon, and so does a `segmented` radio. |

The default variant of a radio or checkbox group shows an HTML label as text, because Flux also puts the label in an HTML attribute there.

### Options in the Slot

The slot takes Flux options, after the options of `options`. Use it for anything that an option object does not cover, such as a create option, an avatar, or `keywords` for the search.

```blade
<x-select label="Project" wire:model="form.project" :options="[1 => 'Website', 2 => 'Mobile app']" placeholder="Choose a project">
    <flux:select.option.create modal="create-project">New project</flux:select.option.create>
</x-select>
```

## Search

The search input filters the options in the browser. `:searchable="false"` removes it, for example from a short list.

```blade
<x-select label="Size" wire:model="form.size" :options="['s' => 'Small', 'm' => 'Medium', 'l' => 'Large']" :searchable="false" placeholder="Choose a size" />
```

## Search on the Server

For a long list, such as all users, let the server find the options. `search-model` names the property that gets the search text, 250 ms after the user stops typing. The browser then shows the options as the server sends them, without filtering them again. `search-placeholder` sets the placeholder of the search input.

```blade
<x-select label="Assignee" wire:model="form.assignee" :options="[1 => 'Aisyah', 2 => 'Ben', 3 => 'Chong']" search-model="form.assignee_search" search-placeholder="Search users" placeholder="Choose a user" />
```

On this page, the options do not change, but the search text reaches its property. In a component, build the options from the search text, and keep the picked option in the list, so the select can still show it:

```html
<x-select label="Assignee" wire:model="assigneeId" :options="$this->assignees" search-model="assigneeSearch" />
```

```php
public string $assigneeSearch = '';

public ?int $assigneeId = null;

/**
 * @return Collection<int, string>
 */
#[Computed]
public function assignees(): Collection
{
    return User::query()
        ->where('name', 'ilike', "%{$this->assigneeSearch}%")
        ->limit(20)
        ->pluck('name', 'id')
        ->union(User::query()->whereKey($this->assigneeId)->pluck('name', 'id'));
}
```

## Default Value

```blade
<x-select label="State" wire:model="form.state" :options="[10 => 'Selangor', 14 => 'Kuala Lumpur', 7 => 'Penang']" :default="14" placeholder="Choose a state" />
```

A checkbox below the select shows "Use the default: Kuala Lumpur". It works the same way as on [`<x-input>`](input.md): a tick picks the default, and an untick picks the option from before. The checkbox takes the label of the default from the options. `default-label` replaces the whole text:

```blade
<x-select label="Branch" wire:model="form.branch" :options="['kl' => 'Kuala Lumpur', 'pg' => 'Penang']" default="kl" default-label="Same as the head office" />
```

`default` works with a select that takes one value. A disabled select disables the checkbox too.

## Many Values

`multiple` lets the user pick more than one option, and the property gets an array. To show the picked options as pills, use [`<x-pillbox>`](pillbox.md).

```blade
<x-select label="Days" wire:model="form.days" :options="['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday']" multiple placeholder="Choose days" />
```

## Variants

`variant="default"` uses the browser's own select, and `variant="combobox"` an input that the user types in to filter the options. Neither has a search input.

```blade
<x-select label="Native" wire:model="form.native_country" variant="default" :options="['MY' => 'Malaysia', 'SG' => 'Singapore']" placeholder="Choose a country" />
<x-select label="Combobox" wire:model="form.combobox_country" variant="combobox" :options="['MY' => 'Malaysia', 'SG' => 'Singapore']" placeholder="Type a country" />
```

## Width

`class` goes to the field, so it sets the width of the label, the select, and the help text. `input-class` goes to the select only.

```blade
<x-select label="Currency" wire:model="form.currency" :options="['MYR' => 'Ringgit', 'SGD' => 'Singapore dollar']" class="max-w-xs" />
```

## Reference

### Props

| Prop                 | Default                    | Description                                                                                                                 |
|----------------------|----------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props          |                            | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `options`            | `[]`                       | The options. See [Options](#options).                                                                                       |
| `variant`            | `listbox`                  | `listbox`, `combobox`, or `default` for the browser's select.                                                               |
| `searchable`         | `true` for the `listbox`   | Adds the search input. Only the `listbox` has one.                                                                          |
| `search-model`       |                            | The property that gets the search text. The browser then stops filtering the options. Works with the `listbox` only.        |
| `search-placeholder` | "Search..."                | The placeholder of the search input. Works with `search-model`.                                                             |
| `default`            |                            | Adds a checkbox below the select that picks this value.                                                                     |
| `default-label`      | "Use the default: *label*" | The label of the default checkbox.                                                                                          |
| `input-class`        |                            | Classes for the select.                                                                                                     |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | Flux options, after the options of `options`.                                  |

### Attributes

`wire:model` goes to `flux:select`. Flux keeps the focus on an element inside the select, so the kit sends the blur event when the focus leaves the select. Every modifier works, also `.blur` and `.live.blur`.

All other attributes go to `flux:select`, so its props work: `placeholder`, `size`, `multiple`, `clearable`, `disabled`, `invalid`, `empty`, `prefix`, `selected-suffix`, `position`, `align`, and `options:class`. `class` goes to the field.
