---
paths:
  - 'app/Domain/**/Transitions/**'
---

# Transitions

## Extend the kit's Transition class
A transition class extends `App\Domain\Shared\Common\Transitions\Transition`, and it takes the place of an Action for its step, so do not wrap a step in a separate Action. Return a reason from `reason()` when the step must not run, and set other attributes in `apply()`. To take input, add constructor parameters after the model, the field, and the new state. Give each one a default, and make `reason()` work without them, because `allowedFor()` builds the transition without input. Do not override `handle()`: it saves in a transaction, and throws `StaleState` when the state changed or the record was deleted after the model was loaded, or `StateNotSaved` when a listener stopped the save. When a step fails, it puts the old attributes back on the model. A step that `reason()` blocks throws `TransitionBlocked`, which carries the reason. Every failed step throws a subclass of Spatie's `CouldNotPerformTransition`.

## Check the permission before each step
Before `transitionTo()`, the caller checks the step with the `transition` policy ability, for example `$this->authorize('transition', [$invoice, 'state', Paid::class])`. The permission name ends with the kebab-case name of the transition class, or of the target state when the step has no class. Add each permission to `config/system.php`. In a Livewire component, call the component's `$this->transitionTo($invoice->state, Paid::class)` instead, which checks the permission and runs the step.
