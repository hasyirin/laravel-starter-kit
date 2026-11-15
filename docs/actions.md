# Action Classes

An Action class holds one business operation, for example updating the information of a user. Livewire components, jobs, and commands call Action classes, so each operation lives in one place and every caller runs the same rules.

## Write an Action Class

Put the class in the Actions folder of its domain, for example `app/Domain/User/Actions/Settings/UpdateUserInformation.php`. Name it after the operation. Use the `Actionable` trait, and give the class one public `execute()` method with typed parameters:

```php
class UpdateUserInformation
{
    use Actionable;

    public function execute(User $user, string $name, string $email, #[\SensitiveParameter] ?string $phone): void
    {
        $user->fill(['name' => $name, 'email' => $email, 'phone' => $phone]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty('phone')) {
            $user->phone_verified_at = null;
        }

        $user->save();
    }
}
```

- An Action class takes models and plain values, not the request.
- An Action class never shows a toast, flashes the session, or redirects. Its caller does that.
- The caller validates the input and authorizes the user before it calls the Action class.
- Every caller, another Action class too, gets the Action class with `UpdateUserInformation::make()`. It comes from the container, so a test can swap it.
- Nothing takes an Action class or another class as a parameter or through a constructor. Get any other class where you use it, with `resolve()`, such as `resolve(DeletePasskey::class)`.
- Every Action class uses the trait, and a test fails when one does not. An Action class that implements a Fortify contract keeps the method name of the contract instead of `execute()`, for example `RegisterUser::create()`.

## Group Action Classes

Keep the Actions folder flat while a domain has few Action classes. When a domain has two or more clear groups, add group folders and put every Action class in one of them. The User domain has five groups:

| Group         | Holds                                               | Example                 |
| ------------- | --------------------------------------------------- | ----------------------- |
| Registration  | How a person becomes a user                         | `RegisterUser`          |
| Security      | How you prove who you are                           | `UpdatePassword`        |
| Settings      | Changes to your own user                            | `UpdateUserInformation` |
| Management    | Work that a user does on other users, by permission | `CreateUser`            |
| Impersonation | The start and the stop of an impersonation          | `StartImpersonation`    |

- Name a group after a topic, not after the area that calls it. An Action class stays in the Management group when a second area starts to call it.
- Give each Action class a name that is unique in its domain, for example `DeleteOtherUser` in the Management group and `DeleteUser` in the Settings group.
- Use `User` before the object in a name only when the operation is on another user, for example `VerifyUserEmail`.
- A group can hold one Action class. Add a second level only when a group is crowded itself.
- Put the test of an Action class in the same group folder under `tests/Feature/Domain`.

## Call an Action Class from a Component

A component validates the input, authorizes the user, calls an Action class for the work, and shows the result. Call the Action class with `make()`:

```php
public function save(): void
{
    /** @var User $user */
    $user = auth()->user();

    $this->validate($this->userInformationRules($user->id, phoneRequired: $this->phoneRequired));

    UpdateUserInformation::make()->execute($user, $this->name, $this->email, $this->phone === '' ? null : $this->phone);

    Flux::toast(variant: 'success', text: __('user/settings.user_information.updated'));
}
```

A component can still read data for its view without an Action class.

## State Steps

A state step needs no Action class, because its transition class holds the work: the checks in `reason()` and the changes in `apply()`. A component runs the step with its own `transitionTo()`, which only checks the permission and shows toasts. An Action class calls `$model->state->transitionTo()` only when the step is one part of a bigger operation. If a component calls such an Action class and the step fails, the component still shows the same toasts. Its `exception()` hook catches the failure. See [Workflows](workflows.md).
