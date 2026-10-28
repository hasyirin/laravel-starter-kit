# Slider

`<x-slider>` picks a number, or a range of two numbers, with a handle that the user drags or moves with the arrow keys. It wraps `flux:slider`.

```blade
<x-slider label="Volume" wire:model="form.volume" />
```

It takes every [field prop](field.md).

## Value

The label row shows the value at its right end. The value follows the handle while the user drags it, and it also changes when Livewire sets the property. A `hint` takes its place, and `:show-value="false"` leaves it out.

```blade
<x-slider label="Brightness" wire:model="form.brightness" hint="Percent" />
<x-slider label="Contrast" wire:model="form.contrast" :show-value="false" />
```

## Limits and Steps

`min` and `max` set the ends, 0 and 100 by default. `step` sets how far one arrow key moves the handle, 1 by default. `big-step` sets how far it moves when the user holds Shift.

```blade
<x-slider label="Discount" wire:model="form.discount" min="0" max="50" step="5" big-step="10" />
```

## Range

`range` adds a second handle. The property gets the start and the end in an array, such as `[200, 800]`. `min-steps-between` keeps the handles that many steps apart.

```blade
<x-slider label="Price" wire:model="form.price" range min="0" max="1000" step="10" min-steps-between="10" />
```

## Marks

Put `flux:slider.tick` in the slot to mark values below the track. A tick with text shows the text, and an empty tick shows a line.

```blade
<x-slider label="Size" wire:model="form.size" min="1" max="3">
    <flux:slider.tick value="1">Small</flux:slider.tick>
    <flux:slider.tick value="2">Medium</flux:slider.tick>
    <flux:slider.tick value="3">Large</flux:slider.tick>
</x-slider>
```

## Width

`class` goes to the field, so it sets the width of the label row, the slider, and the help text. `input-class` goes to the slider only.

```blade
<x-slider label="Opacity" wire:model="form.opacity" class="max-w-xs" />
```

## Reference

### Props

| Prop          | Default | Description                                                                                                                 |
|---------------|---------|-----------------------------------------------------------------------------------------------------------------------------|
| Field props   |         | `label`, `required`, `badge`, `hint`, `description`, `description:trailing`, and `no-error-message`. See [Field](field.md). |
| `show-value`  | `true`  | Shows the value at the end of the label row when the slider has no `hint`.                                                  |
| `input-class` |         | Classes for the slider.                                                                                                     |

### Slots

| Slot        | Description                                                                    |
|-------------|--------------------------------------------------------------------------------|
| Field slots | `label`, `badge`, `hint`, `actions`, and `description`. See [Field](field.md). |
| `default`   | `flux:slider.tick` marks.                                                      |

### Attributes

All other attributes go to `flux:slider`. These include `wire:model` with any modifier, `name`, `value`, `range`, `min`, `max`, `step`, `big-step`, `min-steps-between`, `tick:position`, `track:class`, `thumb:class`, and `disabled`. `class` goes to the field.
