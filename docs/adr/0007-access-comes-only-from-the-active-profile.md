# Access Comes Only from the Active Profile

A user's roles and permissions come only from their active profile. Spatie's traits also let a user hold roles and permissions directly. But a direct grant is in no snapshot, no admin page shows it, and no Action class logs it to the audit log. So `User` refuses direct grants, and tests grant through a profile. See [ADR-0002](0002-profiles-are-append-only-snapshots.md).

## Considered Options

- **Direct grants as a second source of access**: rejected. The admin pages would have to show them, and the audit log would have to record them. A role delete would also have to stop for them.
- **Direct grants as a shortcut for tests only**: rejected. The tests would then give access in a way that the app never uses.

## Consequences

- `assignRole()`, `givePermissionTo()`, and the sync, remove, and revoke methods on `User` throw a `LogicException`. `Role::assignToModels()`, `Role::removeFromModels()`, and `Role::syncModels()` refuse users.
- The read methods on `User`, such as `getPermissionNames()` and `hasExactRoles()`, read only the active profile.
- To give a user access in a test, create a profile with `ProfileFactory::withRoles()` or `ProfileFactory::withPermissions()`. To change the access of a user who already has a profile, use `grantPermissions()`, `revokePermissions()`, or `grantRoles()` from `tests/Pest.php`. Each one creates the next profile. It keeps the placement, roles, and permissions of the active profile, and adds or removes the given ones.
- A change to a role still applies at once to every profile that holds it. The audit log records what the role allowed before and after the change.
