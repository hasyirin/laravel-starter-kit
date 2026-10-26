# Password

`<x-input.password>` is `<x-input type="password" viewable>`. The input hides the password, and a button in the input shows it.

```blade
<x-input.password label="Password" wire:model="form.password" autocomplete="current-password" />
```

It takes every prop and slot of [`<x-input>`](input.md). To link to the password reset page from the label row, see the hint on [Field](field.md).

## Autocomplete

Set `autocomplete`, so that password managers fill in the right password. Use `current-password` on a login form and `new-password` where the user sets a password.

```blade
<x-input.password label="New password" wire:model="form.new_password" autocomplete="new-password" />
```

## Reference

### Props

| Prop                  | Description                                                                 |
|-----------------------|-----------------------------------------------------------------------------|
| Field and input props | Every prop of [`<x-input>`](input.md). `type` and `viewable` are already set. |

### Slots

| Slot                  | Description                                     |
|-----------------------|-------------------------------------------------|
| Field and input slots | Every slot of [`<x-input>`](input.md).          |

### Attributes

All other attributes go to the input, such as `wire:model`, `autocomplete`, and `placeholder`. `class` goes to the field.
