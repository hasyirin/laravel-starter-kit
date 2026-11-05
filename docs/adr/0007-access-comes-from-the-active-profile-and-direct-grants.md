# Access Comes from the Active Profile and Direct Grants

A user's roles and permissions come from their active profile and from direct grants. A direct grant is a role or permission that is given to the user, not to a profile. The kit itself gives access only through profiles, in its seeder, admin pages, and Action classes. But it is a starter kit, so it lets an app choose direct grants too. See [ADR-0002](0002-profiles-are-append-only-snapshots.md).

## Considered Options

- **Access only from the active profile**: rejected. `User` would refuse Spatie's grant methods, so an app could not choose direct grants.
- **No block, but checks that read only the active profile**: rejected. A direct grant would save, but no check would read it. So the grant would fail without an error.
- **Direct grants in the tests**: rejected. The kit gives access through profiles, so the tests give access in the same way.

## Consequences

- The checks, lists, and query scopes on `User`, such as `hasRole()` and `getPermissionNames()`, count the direct grants and the active profile.
- A direct grant is in no snapshot. The user pages do not show it, and no Action class records it in the audit log. An app that uses direct grants must show and record them itself.
- A direct grant does not stop `Role::delete()`. The delete removes the direct grants of that role.
- To give a user access in a test, create a profile with `ProfileFactory::withRoles()` or `ProfileFactory::withPermissions()`. To change the access of a user who already has a profile, use `grantPermissions()`, `revokePermissions()`, or `grantRoles()` from `tests/Pest.php`. Each one creates the next profile. It keeps the placement, roles, and permissions of the active profile, and adds or removes the given ones.
- A change to a role applies at once to every profile and user that holds it. The audit log records what the role allowed before and after the change.
