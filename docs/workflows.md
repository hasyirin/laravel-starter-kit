# Workflows

A workflow moves a record through states, for example an application from review to site visit to approval. The kit builds workflows on Spatie Model States. Its base classes in `app/Domain/Shared/Common` add labels, permissions, safe saves, and docs.

## Add a State Column

1. Add a string column for the state to the table.
2. Make a folder for the column, for example app/Domain/Billing/States/InvoiceState. Put the abstract base state and all its states in this one folder, because Spatie finds the states by their folder.
3. Make the base state extend `App\Domain\Shared\Common\States\State`. Set its default state with the `#[DefaultState]` attribute, and list its transitions in `configure()`:

```php
#[DefaultState(Draft::class)]
abstract class InvoiceState extends State
{
    protected static function configure(StateConfig $config): StateConfig
    {
        return $config
            ->allowTransition(Draft::class, Sent::class)
            ->allowTransition(Sent::class, Overdue::class)
            ->allowTransition([Sent::class, Overdue::class], Paid::class, Pay::class);
    }
}
```

4. Give each state a name in snake case, for example `public static string $name = 'site_visit';`. Spatie stores this name in the column, and its docs do not allow hyphens in names.
5. In the model, use `Spatie\ModelStates\HasStates` and cast the column to the base state.

Write each transition as one `allowTransition()` call, and put all the states that a step can start from in one array. A loop is a step back to an earlier state. Do not use the `#[AllowTransition]` attribute.

The kit builds the config of a state column once and keeps it for the life of the PHP process. That is one request on PHP-FPM, but many requests or jobs in an Octane worker or a queue worker. Spatie would otherwise build the config again for every record and every check, which is slow for a flow with many states. So:

- List fixed rules only in `configure()`. Do not read the user, the request, the config, or the database in it. Put a check that depends on the record or the user in a transition's `reason()` or in the policy.
- Override `configure()` only in the base state. The kit builds one config for the whole column, from the first state that asks for it.
- Do not override `config()`. It is final, and it returns a copy, so a change to its result does not last.

A model can have more than one state column, for example a decision and a stage. Each column has its own base state. A transition can check the other column in `reason()`.

## Labels and Colors

A state's label is the headline of its class name, for example "Site Visit", and its color is gray. To change them, override `label()` or `color()` in the state. Do not read the model in them, because `options()` and the docs make states without a model. The base state's `options()` returns each label by state name, for a select field.

The base state uses the kit's label and color contracts, so Filament text and badge columns show its label, as they do for enums. An `->html()` column shows the stored name instead, because Spatie states are `Stringable`.

## Add a Transition Class

A step without a class only changes the state. Give a step a class when it needs a check or other changes. The class extends `App\Domain\Shared\Common\Transitions\Transition`:

```php
class Pay extends Transition
{
    public function reason(): ?string
    {
        return $this->model->balance > 0 ? __('billing/invoices.unpaid') : null;
    }

    protected function apply(): void
    {
        $this->model->paid_at = now();
    }
}
```

- `reason()` returns why the step must not run, or null. The step runs only when it returns null.
- `apply()` changes other attributes. They save together with the new state.
- Do not override `handle()`. It saves in a database transaction. It throws `StaleState` when another request changed the state or deleted the record after the model was loaded, and `StateNotSaved` when a listener stopped the save. When a step fails, it puts the old attributes back on the model, so the model matches the database again.

To give a step input, such as a payment reference, add a constructor parameter after the model, the field, and the new state. Then pass the value after the target state, as in `$invoice->state->transitionTo(Paid::class, $reference)`. Give the parameter a default, and make `reason()` work without it, because `allowedFor()` builds each transition without input:

```php
public function __construct(Invoice $model, string $field, State $newState, private readonly ?string $reference = null)
{
    parent::__construct($model, $field, $newState);
}
```

## Run a Step

Check the permission first, then run the step:

```php
$this->authorize('transition', [$invoice, 'state', Paid::class]);

$invoice->state->transitionTo(Paid::class);
```

- `Policy::transition()` checks the permission of the step. The permission name ends with the step: the kebab-case name of the transition class, or of the target state when the step has no class. The policy prefix and the plural model name come first, for example internal.invoices.pay. Add each permission to `config/system.php`.
- The policy refuses a step that the current state does not have, for example when another user moved the record after the page loaded. In a Livewire component, the component's `transitionTo()` below turns this into a toast.
- `hasStepTo()` on a state tells whether the config has a step to a state, without the checks and the permissions.
- `allowedFor()` on a state returns the states that a user can move to now, after the checks and the permissions. Use it for the buttons.
- `reasonFor()` on a state returns why a step is blocked.
- When `reason()` blocks a step, `transitionTo()` throws `App\Domain\Shared\Common\Exceptions\TransitionBlocked`, with the reason in its `reason` property. It extends `Spatie\ModelStates\Exceptions\TransitionNotAllowed`.
- `StaleState`, `StateNotSaved`, and `TransitionBlocked` all extend `Spatie\ModelStates\Exceptions\CouldNotPerformTransition`, so one catch handles any step that fails.

### In a Livewire Component

In a component that extends `App\Livewire\Component`, call the component's own `transitionTo()` instead of the state's. It checks the permission and runs the step, and it shows a Flux toast instead of an error page when the step fails:

```php
public function pay(): void
{
    if ($this->transitionTo($this->invoice->state, Paid::class)) {
        Flux::toast(variant: 'success', text: __('billing/invoices.paid'));
    }
}
```

- It holds no business work. The step's work stays in its transition class, which takes the place of an [Action](actions.md) for a step.
- It returns true when the step ran, and false when the record changed before the step could start.
- It passes extra arguments to the transition class, as in `$this->transitionTo($this->invoice->state, Paid::class, $reference)`.
- When the record changed after the page loaded, so the step no longer exists or another request saved first, it shows a toast and reloads the page with `wire:navigate`.
- When `reason()` blocks the step, it shows the reason in a toast and keeps the page, so the user keeps what they typed.
- When a listener stopped the save, it shows a toast.
- When the user does not have the permission, it still fails with a 403. The buttons from `allowedFor()` never offer such a step, so only a removed permission or a forged request gets there.

The toasts come from the `exception()` hook of `App\Livewire\Component`. It catches a failed step anywhere in a component action, so a step that an Action runs inside a bigger operation gets the same toasts. Any other error still shows the error page. A component that defines its own `exception()` calls `parent::exception($e, $stopPropagation)` first.

## Listen for Changes

After each step, the kit sends `App\Domain\Shared\Common\Events\StateChanged`. It waits until the database transaction commits, so a listener never sees a change that rolls back.

## Docs

Three sections of `php artisan docs:generate` read a state column. Each takes the model class and the field:

```markdown
<!-- generated:state-diagram model="App\Domain\Billing\Models\Invoice" field="state" -->
<!-- /generated:state-diagram -->
```

- `state-diagram`: a Mermaid state diagram. Each arrow shows its step.
- `transitions`: a table of the steps, with the permission and the roles of each step.
- `swimlanes`: a Mermaid flowchart with one lane for each group of roles that can do steps.

When every other state can move to a state by the same step, the sections show that step once, from "Any state". So a flow where officers can send a record to a state from anywhere, or a flow with `allowAllTransitions()`, stays readable. This needs at least four states, so a small flow keeps all its arrows.
