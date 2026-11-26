# Passkey Verify

`<x-passkey-verify>` is a button that signs the user in, or confirms the user, with a passkey. It asks the server for options, lets the browser check the passkey, and sends the result back.

```blade
<x-passkey-verify />
```

The button renders only when the browser supports passkeys, which `window.Passkeys.isSupported()` tells. In a browser without support, the preview is empty.

After a success, the page goes to the `redirect` of the response, or to `/dashboard`. If the check fails, the component shows the error message under the button. If the user cancels, it shows no error.

## Layout

Without `compact`, the component shows a button as wide as its container. A divider with the `separator` text follows it. Use this layout before a form, so the divider separates the button from the form.

With `compact`, the divider comes first. A centred square button with a fingerprint icon follows it. A tooltip shows the `label`. Use this layout after a form, as on the login page.

```blade
<x-passkey-verify compact />
```

## Routes

`optionsRoute` and `submitRoute` are route names. The first gives the options for the browser. The second receives the result. The defaults are `passkey.login-options` and `passkey.login`.

```blade
<x-passkey-verify options-route="passkey.confirm-options" submit-route="passkey.confirm" />
```

## Texts

`label` is the text of the button, and the tooltip with `compact`. `loadingLabel` replaces it while the check runs. `separator` is the text in the divider. The defaults come from the `user/passkeys.verify` translation keys.

The confirm password page sets all three, with the `user/passkeys.confirm` keys:

```blade
<x-passkey-verify
    options-route="passkey.confirm-options"
    submit-route="passkey.confirm"
    :label="__('user/passkeys.confirm.button')"
    :loading-label="__('user/passkeys.confirm.loading')"
    :separator="__('user/passkeys.confirm.separator')"
/>
```

## Reference

### Props

| Prop           | Default                          | Description                                                       |
| -------------- | -------------------------------- | ----------------------------------------------------------------- |
| `optionsRoute` | `passkey.login-options`          | The route name that gives the passkey options.                    |
| `submitRoute`  | `passkey.login`                  | The route name that receives the result.                          |
| `label`        | `user/passkeys.verify.button`    | The text of the button, and of the tooltip with `compact`.        |
| `loadingLabel` | `user/passkeys.verify.loading`   | The text while the check runs.                                    |
| `separator`    | `user/passkeys.verify.separator` | The text in the divider.                                          |
| `compact`      | `false`                          | Show the divider first, then a square icon button with a tooltip. |

### Slots

The component has no slots.

### Attributes

The component does not pass other attributes to an element.
