# Workflows

A workflow moves a record through states, for example an application from review to site visit to approval. The kit builds workflows on Spatie Model States. Its base classes in `app/Domain/Shared/Common` add labels, permissions, safe saves, and docs.

## Add a State Column

1. Add a string column for the state to the table.
2. Make a folder for the column, for example app/Domain/Billing/States/InvoiceState. Put the abstract base state and all its states in this one folder, because Spatie finds the states by their folder.
3. Make the base state extend `App\Domain\Shared\Common\States\State`. Set its default state with the `#[DefaultState]` attribute, and list its transitions in `config()`:

```php
#[DefaultState(Draft::class)]
abstract class InvoiceState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->allowTransition(Draft::class, Sent::class)
            ->allowTransition(Sent::class, Overdue::class)
            ->allowTransition([Sent::class, Overdue::class], Paid::class, Pay::class);
    }
}
```

4. Give each state a name in snake case, for example `public static string $name = 'site_visit';`. Spatie stores this name in the column, and its docs do not allow hyphens in names.
5. In the model, use `Spatie\ModelStates\HasStates` and cast the column to the base state.

Write each transition as one `allowTransition()` call, and put all the states that a step can start from in one array. A loop is a step back to an earlier state. Do not use the `#[AllowTransition]` attribute.

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
- Do not override `handle()`. It saves in a database transaction, and it throws `StaleState` when another request changed the state after the model was loaded.

## Run a Step

Check the permission first, then run the step:

```php
$this->authorize('transition', [$invoice, 'state', Paid::class]);

$invoice->state->transitionTo(Paid::class);
```

- `Policy::transition()` checks the permission of the step. The permission name ends with the step: the kebab-case name of the transition class, or of the target state when the step has no class. The policy prefix and the plural model name come first, for example internal.invoices.pay. Add each permission to `config/system.php`.
- `allowedFor()` on a state returns the states that a user can move to now, after the checks and the permissions. Use it for the buttons.
- `reasonFor()` on a state returns why a step is blocked.
- When `reason()` blocks a step, `transitionTo()` throws `Spatie\ModelStates\Exceptions\TransitionNotAllowed`.

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
