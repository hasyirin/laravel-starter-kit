---
paths:
  - 'app/Domain/**/Policies/*.php'
---

# Policies

## Extend the shared permission Policy
Extend `App\Domain\Shared\Common\Policies\Policy` and check permissions with `hasPermission()`, `hasAnyPermission()`, or `hasAllPermissions()`. Define each permission in `config/system.php` at `permissions.<prefix>.<plural-kebab-model>.<action>`, where `<prefix>` is the policy's `$prefix`.

## Give a sub-part of a model a type
An ability for a part of a model, such as the roles of a user, takes `?string $type = null` as its last parameter. It passes when the base check passes and `hasTypedPermission()` finds `<ability>-<type>`, for example `update-roles` or `view-activities`. Define that permission in `config/system.php`. A typed `create` ability checks `update-<type>`, not `create-<type>`. Setting a part when you create a record gives the same power as changing it later. A name such as `users.create-roles` also reads like the `roles.create` permission.
