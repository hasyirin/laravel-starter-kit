# Switch

`<x-switch>` turns a setting on or off. It wraps `flux:switch`, and the property gets `true` or `false`.

```blade
<x-switch label="Active" wire:model="form.active" />
```

A switch has its label beside it, so it does not use the field props. Flux shows its label, its description, and the error of its property.

## Defaults

The kit changes this Flux default:

| Prop    | Kit default | Flux default |
|---------|-------------|--------------|
| `align` | `left`      | `right`      |

So the switch comes before its label, like a checkbox. `align="right"` puts the label first and the switch at the end of the row, as in a list of settings.

```blade
<x-switch label="Email notifications" wire:model="form.email_notifications" align="right" />
```

## Description

```blade
<x-switch label="Active" description="Users who are not active cannot log in." wire:model="form.user_active" />
```

## Reference

### Props

| Prop    | Default | Description                                                           |
|---------|---------|-----------------------------------------------------------------------|
| `align` | `left`  | `left` puts the switch before the label, and `right` after the label. |

### Attributes

All other attributes go to `flux:switch`. These include `wire:model` with any modifier, `label`, `description`, `name`, `value`, `checked`, and `disabled`.
