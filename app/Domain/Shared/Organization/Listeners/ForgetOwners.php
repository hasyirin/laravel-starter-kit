<?php

declare(strict_types=1);

namespace App\Domain\Shared\Organization\Listeners;

use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use Illuminate\Database\Events\MigrationsEnded;

class ForgetOwners
{
    public function handle(MigrationsEnded $event): void
    {
        if ($event->method !== 'up') {
            return;
        }

        Organization::forgetOwner();
        Department::forgetOwner();
        Branch::forgetOwner();
    }
}
