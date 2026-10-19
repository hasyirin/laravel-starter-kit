<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Observers;

use App\Domain\Shared\Organization\Models\Organization;
use LogicException;

class OrganizationObserver
{
    public function saved(): void
    {
        Organization::forgetOwner();
    }

    public function deleting(Organization $organization): void
    {
        throw_if($organization->is_owner, LogicException::class, 'The owner organization cannot be deleted.');
    }

    public function deleted(): void
    {
        Organization::forgetOwner();
    }

    public function restored(): void
    {
        Organization::forgetOwner();
    }
}
