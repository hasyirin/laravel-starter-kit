---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Use short translation keys
Translate UI text with short keys from PHP files under `lang/en/`, such as `__('user/settings.profile.updated')`, never JSON sentence keys. Put shared labels in `lang/en/common/` (`buttons.php`, `inputs.php`).

## Keep components thin
A component validates the input, authorizes the user, calls an Action for the work, and shows the result: a toast, a redirect, or state for the view. Inject the Action into the component method, for example `public function updateProfileInformation(UpdateProfile $updateProfile): void`, and call its `execute()`. Do not write models or apply domain rules in a component. Reading data for the view is fine.

## Run workflow steps with the component's transitionTo()
Run a state step with the component's `$this->transitionTo($model->state, Paid::class)` from `App\Livewire\Component`, not with `authorize()` and the state's `transitionTo()`. It checks the permission, and it turns a changed record or a blocked step into a Flux toast instead of an error page.

## Never use em dashes
Never write an em dash (`—` or `&mdash;`) in the frontend. Rewrite the sentence, or use a comma, colon, period, or parentheses instead.

## Cut secondary text that adds no information
Add a description, hint, or secondary label only when it tells the user something the heading and controls do not. Examples are a reason, a consequence, a next step, or what a feature does. Cut text that only repeats the heading. If a screen looks bare without it, fix the layout. If you are not sure, ask the user.
