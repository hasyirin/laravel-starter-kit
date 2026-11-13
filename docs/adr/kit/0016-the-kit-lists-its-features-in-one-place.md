# The Kit Lists Its Features in One Place

`system.features` in `config/system.php` lists every feature that an app can turn on or off, as cases of the `Feature` enum. The list holds the kit features, such as phone verification and the SMS password reset, and the Fortify features, such as registration and passkeys. `system.feature_options` holds the options of a feature. Right after the config loads, before any service provider runs, the kit copies the Fortify cases and the options into `fortify.features` and `fortify-options`. Fortify reads its own config while it registers, in its routes, and in its controllers. App code and tests ask `Feature`, never `Laravel\Fortify\Features`. A kit test that needs a feature skips when the app turns the feature off.

## Considered Options

- **Remove `MustVerifyPhone` from `User` to turn phone verification off**: rejected. It turned off only the middleware. The verification page, the SMS password reset, and the notices stayed. The tests could only ask the class, and an app had to edit a kit file to make the choice.
- **One switch for SMS**: rejected. Phone verification and the SMS password reset are separate features, and an app can want the verification without the reset. One list also covers features that do not use SMS.
- **Add the kit features to `fortify.features`**: rejected. Fortify owns that config. Kit features in it would look like Fortify features, and a Fortify upgrade could use the same names.
- **Keep two lists, Fortify's and the kit's**: rejected. An app would turn features on in two files, and code would ask two classes.

## Consequences

- `config/fortify.php` has an empty `features` list. A change there does nothing, because the kit overwrites it.
- A Fortify feature that the kit does not use, such as `update-passwords`, has no case. An app that needs one adds a case.
- A test that changes `system.features` at runtime calls `Feature::syncFortify()`, or the `TestCase` helpers that call it, so Fortify sees the change.
- The kit registers the routes of a feature only when the feature is on. With cached routes, run `php artisan route:clear` after a change to `system.features`. In tests, `enableFeature()` reloads only the Fortify routes, and `disableFeature()` removes no route.
- The app throws on boot when `system.features` has the SMS password reset without phone verification or without the password reset. The SMS reset sends codes only to verified numbers, and it ends on the reset page of the password reset.
- Every page, link, and email follows the list, so a test that needs a feature skips when the feature is off. The suite passes with any feature off.
