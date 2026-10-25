---
paths:
  - 'app/Domain/**/Transitions/**'
---

# Transitions

## Extend the kit's Transition class
A transition class extends `App\Domain\Shared\Common\Transitions\Transition`. Return a reason from `reason()` when the step must not run, and set other attributes in `apply()`. Do not override `handle()`: it saves in a transaction and fails when the state changed after the model was loaded.

## Check the permission before each step
Before `transitionTo()`, the caller checks the step with the `transition` policy ability, for example `$this->authorize('transition', [$invoice, 'state', Paid::class])`. The permission name ends with the kebab-case name of the transition class, or of the target state when the step has no class. Add each permission to `config/system.php`.
