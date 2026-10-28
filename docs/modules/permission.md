# Permissions

The app checks access with permissions, and roles group permissions. A user gets the roles and permissions of their active profile, plus any given to the user directly. See **Profiles** in the README.

## Add a Permission

1. Add the permission to `permissions` in `config/system.php`. Give it a key path that matches its name, for example `admin.controls.roles.view`.
2. Run `php artisan db:seed --class="App\Domain\Shared\Permission\Seeders\PermissionSeeder"`. It creates the new permission and deletes the permissions that are no longer in the config. Do not run all the seeders in production, because `UserSeeder` creates the default users.
3. Check the permission in a policy that extends `Policy`. `Policy::path()` builds the config key of a permission from the root, the policy prefix, the plural model name, and the action. For example, `RolePolicy` checks `admin.controls.roles.view` for the `view` action.

## Add a Role

Add the role to `RoleSeeder::data()`, or to the `data()` method of a seeder that extends `RoleSeeder`. Call a new role seeder from `DatabaseSeeder`, so that the seeding and the permission matrix include it. Each role lists permission patterns and exceptions, and `*` matches any text. `RoleSeeder` only adds permissions to a role, so changes made in production stay.

The seeders create system roles. A system role cannot be renamed or deleted.

## Bypass

A user with `bypass-authorization` passes every check, because `AppServiceProvider` checks it in `Gate::before`. The super admin role has this permission.

## Permission Matrix

The roles that the seeders create, and the permissions of each role. `php artisan docs:generate` writes this table.

<!-- generated:permission-matrix -->

| Permission                                | Super Administrator |
|-------------------------------------------|---------------------|
| `bypass-authorization`                    | ✓                   |
| `internal.access`                         | ✓                   |
| `admin.access`                            | ✓                   |
| `admin.horizon.view`                      | ✓                   |
| `admin.controls.roles.view-any`           | ✓                   |
| `admin.controls.roles.view`               | ✓                   |
| `admin.controls.roles.create`             | ✓                   |
| `admin.controls.roles.update`             | ✓                   |
| `admin.controls.roles.update-permissions` | ✓                   |
| `admin.controls.roles.delete`             | ✓                   |
| `admin.controls.users.view-any`           | ✓                   |
| `admin.controls.users.view`               | ✓                   |
| `admin.controls.users.create`             | ✓                   |
| `admin.controls.users.update`             | ✓                   |
| `admin.controls.users.update-permissions` | ✓                   |
| `admin.controls.users.update-roles`       | ✓                   |
| `admin.controls.users.delete`             | ✓                   |

A role with `bypass-authorization` passes every check.

<!-- /generated:permission-matrix -->

## Rules

The names of the tests in `tests/Feature/Domain/Shared/Permission`. A failing test fails the build, so each rule is true.

<!-- generated:test-rules path="tests/Feature/Domain/Shared/Permission" -->

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
- the permission helpers and scopes count the permissions of the active profile

### HasRoles

- a user has the roles of their active profile
- a user does not have the roles of an inactive profile
- the role helpers and scopes count the roles of the active profile

### Role

- a new role is not a system role
- the label of a system role is translated
- the label of a role that is not a system role is its name
- role changes are recorded in the activity log
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
- a user can view a role they have
- create needs the create permission
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

- seeding creates only the super admin role, with the bypass-authorization permission
- seeding leaves out permissions that match an except pattern
- seeding keeps permissions that were given to a role later
- seeding reuses an existing role with the same name

<!-- /generated:test-rules -->
