<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Policies;

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\User\Models\User;

class AuditEntryPolicy extends Policy
{
    protected static string $prefix = 'system.logs';

    protected static ?string $segment = 'audits';

    public function view(User $user, AuditEntry $entry): bool
    {
        return $this->hasPermission($user, 'view');
    }
}
