---
paths:
  - 'app/Domain/**/Policies/*.php'
---

# Policies

## Extend the shared permission Policy
Extend `App\Domain\Shared\Common\Policies\Policy` and check permissions with `hasPermission()`, `hasAnyPermission()`, or `hasAllPermissions()`. Define each permission in `config/system.php` at `permissions.<prefix>.<plural-kebab-model>.<action>`, where `<prefix>` is the policy's `$prefix`.
