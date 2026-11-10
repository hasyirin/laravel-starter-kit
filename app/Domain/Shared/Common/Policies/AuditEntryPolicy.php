<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Policies;

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\User\Models\User;

class AuditEntryPolicy
{
    public function view(User $user, AuditEntry $entry): bool
    {
        return $user->hasPermissionTo(config()->string('system.permissions.system.audit-log.view'));
    }
}
