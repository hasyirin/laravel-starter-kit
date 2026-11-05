---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Use short translation keys
Translate UI text with short keys from PHP files under `lang/en/`, such as `__('user/settings.user_information.updated')`, never JSON sentence keys. Put shared labels in `lang/en/common/` (`buttons.php`, `inputs.php`).

## Keep components thin
A component authorizes the user and starts the work. Then it shows the result: a toast, a redirect, or state for the view. If the component has a form object, call the form's `save()`. If not, validate the input and call the Action class, for example `UpdateUserInformation::make()->execute($user, $this->name, $this->email)`. Do not write models or apply domain rules in a component. Reading data for the view is fine.

## Build forms with set() and save()
Extend `App\Livewire\Form`. Put a form in `app/Livewire/Forms/<Area>/<Model>Form.php`. Give it typed properties and a `rules()` method; do not use `#[Validate]`. Use one form for create and edit:
- `set(Model $model): static` fills the form from a record.
- `save(): Model` validates, then calls the Action class with `make()->execute()`, and returns the record. If the record is null, it creates one.
- A form never writes models and never opens a transaction. The Action class does that.
- Mark each property that the user does not type with `#[Locked]`, such as the record and any flag that changes the rules. A browser can change any other public property.
- Do not put a `disabled` flag on a form. Disable the component with `CanBeDisabled`, and check a part that the user can or cannot change with the policy in `save()`.

## Take route values as locked properties
Give a page component a typed public property for each route parameter, such as `#[Locked] public Role $role;`. Livewire fills it before `mount()`, so do not add the value as a `mount()` parameter or copy it in `mount()`. Use `mount()` only to authorize and to fill the form. Mark every route property with `#[Locked]`, because a browser can change any other public property. Pass a record to a child component as a locked model property, such as `:$role`, not as its id.

## Run workflow steps with the component's transitionTo()
Run a state step with the component's `$this->transitionTo($model->state, Paid::class)` from `App\Livewire\Component`, not with `authorize()` and the state's `transitionTo()`. It checks the permission. The `exception()` hook of the base component turns any failed step into a Flux toast. This includes a step that an Action class runs, so do not catch step exceptions in a component. If a component defines its own `exception()`, call `parent::exception($e, $stopPropagation)` first.

## Never use em dashes
Never write an em dash (`—` or `&mdash;`) in the frontend. Rewrite the sentence, or use a comma, colon, period, or parentheses instead.

## Cut secondary text that adds no information
Add a description, hint, or secondary label only when it tells the user something the heading and controls do not. Examples are a reason, a consequence, a next step, or what a feature does. Cut text that only repeats the heading. If a screen looks bare without it, fix the layout. If you are not sure, ask the user.

## Type dates as Carbon in Livewire forms
Type date and date-time properties of Livewire forms and components as `?CarbonImmutable`, the date class of the app, not as `string`. Use `Flux\DateRange` and `Flux\DateTimeRange` for ranges. Keep a time of day as an `H:i` string, because Carbon always holds a date.

Keep the values in the app time zone, `app.timezone`. `<x-input.datetime>` shows and edits them in the time zone of the user, so do not convert them yourself.
