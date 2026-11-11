# Permissions

The app checks access with permissions, and roles group permissions. A user gets the roles and permissions of their active profile, plus any given to the user directly. See **Profiles** in the README.

## Add a Permission

1. Add the permission to `permissions` in `config/system.php`. Give it a key path that matches its name, for example `internal.controls.roles.view`.
2. Run `php artisan db:seed --class="App\Domain\Shared\Permission\Seeders\PermissionSeeder"`. It creates the new permission and deletes the permissions that are no longer in the config. Do not run all the seeders in production, because `UserSeeder` creates the default users.
3. Check the permission in a policy that extends `Policy`. `Policy::path()` builds the config key of a permission from the root, the policy prefix, the plural model name, and the ability. For example, `RolePolicy` checks `internal.controls.roles.view` for the `view` ability.

## Add a Role

Add the role to `RoleSeeder::data()`, or to the `data()` method of a seeder that extends `RoleSeeder`. Call a new role seeder from `DatabaseSeeder`, so that the seeding and the permission matrix include it. Each role lists permission patterns and exceptions, and `*` matches any text. `RoleSeeder` only adds permissions to a role, so the changes that people make in production stay.

The seeders create system roles. Nobody can rename or delete a system role.

## Bypass

A user with `bypass-authorization` passes every check, because `AppServiceProvider` checks it in `Gate::before`. The `system-admin` role has this permission.

## Permission Matrix

The roles that the seeders create, and the permissions of each role. `php artisan docs:generate` writes this table.

<!-- generated:permission-matrix -->

| Permission                                       | System Administrator |
|--------------------------------------------------|----------------------|
| `bypass-authorization`                           | ✓                    |
| `internal.access`                                | ✓                    |
| `internal.controls.roles.view-any`               | ✓                    |
| `internal.controls.roles.view`                   | ✓                    |
| `internal.controls.roles.create`                 | ✓                    |
| `internal.controls.roles.update`                 | ✓                    |
| `internal.controls.roles.update-permissions`     | ✓                    |
| `internal.controls.roles.delete`                 | ✓                    |
| `internal.controls.roles.view-audit-log`         | ✓                    |
| `internal.controls.users.view-any`               | ✓                    |
| `internal.controls.users.view`                   | ✓                    |
| `internal.controls.users.create`                 | ✓                    |
| `internal.controls.users.update`                 | ✓                    |
| `internal.controls.users.update-permissions`     | ✓                    |
| `internal.controls.users.update-roles`           | ✓                    |
| `internal.controls.users.delete`                 | ✓                    |
| `internal.controls.users.impersonate`            | ✓                    |
| `internal.controls.users.view-audit-log`         | ✓                    |
| `internal.controls.organizations.view-any`       | ✓                    |
| `internal.controls.organizations.view`           | ✓                    |
| `internal.controls.organizations.create`         | ✓                    |
| `internal.controls.organizations.update`         | ✓                    |
| `internal.controls.organizations.delete`         | ✓                    |
| `internal.controls.organizations.view-audit-log` | ✓                    |
| `internal.controls.letterheads.view-any`         | ✓                    |
| `internal.controls.letterheads.view`             | ✓                    |
| `internal.controls.letterheads.create`           | ✓                    |
| `internal.controls.letterheads.update`           | ✓                    |
| `internal.controls.letterheads.delete`           | ✓                    |
| `system.access`                                  | ✓                    |
| `system.horizon.view`                            | ✓                    |
| `system.logs.audits.view`                        | ✓                    |
| `system.logs.messages.view`                      | ✓                    |
| `system.logs.messages.resend`                    | ✓                    |
| `system.letters.compose`                         | ✓                    |

A role with `bypass-authorization` passes every check.

<!-- /generated:permission-matrix -->

## Rules

The names of the tests in `tests/Feature/Domain/Shared/Permission`. A failing test fails the build, so each rule is true.

<!-- generated:test-rules path="tests/Feature/Domain/Shared/Permission" -->

### CreateRole

- a new role gets the web guard and its permissions
- a new role is logged once with its permissions
- bypass-authorization cannot be given without bypass
- bypass-authorization can be given with bypass

### DeleteRole

- a role is deleted
- a role with bypass-authorization cannot be deleted without bypass

### UpdateRole

- the name and the permissions change in one logged update
- a change to the permissions alone is logged
- nothing is logged when nothing changes
- the permissions stay when they are not given
- a role with bypass-authorization cannot be changed without bypass
- bypass-authorization cannot be added without bypass
- bypass-authorization cannot be removed from a role that you hold
- bypass-authorization can be removed from another role with bypass

### HasFrozenRoles

- a model keeps the roles and permissions that it gets before its first save
- a model keeps the roles and permissions from every call before its first save
- syncing before the first save replaces the roles and permissions given before
- saving a model with roles returns false when a listener stops the save
- a model is not saved when one of its roles does not exist
- the roles and permissions of a model cannot change after its first save
- roles and permissions cannot be removed even before the first save

### HasPermissions

- a user has the permissions of their active profile
- a user does not have the permissions of an inactive profile
- the permission helpers and scopes count the direct grants and the active profile
- the permissions through roles count the direct grants and the active profile
- a user can get and lose permissions directly

### HasRoles

- a user has the roles of their active profile
- a user does not have the roles of an inactive profile
- the role helpers and scopes count the direct grants and the active profile
- a user can get and lose roles directly

### Role

- a new role is not a system role
- the label of a system role is translated
- the label of a role that is not a system role is its name
- role changes are recorded in the audit log
- a role knows when a profile holds it
- is_system cannot be mass assigned
- a system role cannot be renamed
- a system role cannot be deleted
- other attributes of a system role can be updated
- a role that is not a system role can be renamed and deleted
- a role that a profile has cannot be deleted
- the profiles of a force deleted user do not keep a role from being deleted
- a role cannot be assigned to a profile
- a role cannot be removed from a profile
- a role that a profile has cannot be synced to other models
- a role cannot be synced to a profile
- a role that no profile has can be synced to users

### RolePolicy

- view any needs the view-any permission
- view needs the view-any or view permission
- having a role does not let a user view it
- viewing a type needs the view permission for that type
- create needs the create permission
- creating a type needs the update permission for that type
- update needs the update permission
- the name of a system role cannot be updated
- updating a type needs the update permission for that type
- delete needs the delete permission
- a system role cannot be deleted

### PermissionSeeder

- seeding creates every permission in the config
- seeding twice does not duplicate permissions
- seeding removes permissions that are no longer in the config

### RoleSeeder

- seeding creates only the system-admin role, with the bypass-authorization permission
- seeding leaves out permissions that match an except pattern
- seeding keeps permissions that were given to a role later
- seeding reuses an existing role with the same name

### PermissionTree

- a resource holds its abilities as permissions
- a permission directly under a group is listed on the group
- a one-segment permission is listed at the root
- a missing label falls back to the headline
- a node with only an access permission is a group

<!-- /generated:test-rules -->
