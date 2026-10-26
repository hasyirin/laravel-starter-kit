# Actions

An Action holds one business operation, for example updating a profile. Livewire components, jobs, and commands call Actions, so each operation lives in one place and every caller runs the same rules.

## Write an Action

Put the class in the Actions folder of its domain, for example `app/Domain/User/Actions/UpdateProfile.php`. Name it after the operation. Use the `Actionable` trait, give the class one public `execute()` method with typed parameters, and take its dependencies through the constructor:

```php
class UpdateProfile
{
    use Actionable;

    public function execute(User $user, string $name, string $email): void
    {
        $user->fill(['name' => $name, 'email' => $email]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
```

- An Action takes models and plain values, not the request.
- An Action never shows a toast, flashes the session, or redirects. Its caller does that.
- The caller validates the input and authorizes the user before it calls the Action.
- Code that cannot inject the Action gets it from the container with `UpdateProfile::make()`.
- Every Action uses the trait, and a test fails when one does not. Fortify actions keep the method names of their Fortify contracts instead of `execute()`, for example `CreateNewUser::create()`.

## Call an Action from a Component

A component validates the input, authorizes the user, calls an Action for the work, and shows the result. Inject the Action into the component method:

```php
public function updateProfileInformation(UpdateProfile $updateProfile): void
{
    /** @var User $user */
    $user = auth()->user();

    $this->validate($this->profileRules($user->id));

    $updateProfile->execute($user, $this->name, $this->email);

    Flux::toast(variant: 'success', text: __('user/settings.profile.updated'));
}
```

A component can still read data for its view without an Action.

## State Steps

A state step needs no Action, because its transition class holds the work: the checks in `reason()` and the changes in `apply()`. A component runs the step with its own `transitionTo()`, which only checks the permission and shows toasts. An Action calls `$model->state->transitionTo()` only when the step is one part of a bigger operation. See [Workflows](workflows.md).
