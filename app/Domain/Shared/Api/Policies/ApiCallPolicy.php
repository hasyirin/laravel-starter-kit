<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Policies;

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\User\Models\User;

class ApiCallPolicy extends Policy
{
    protected static string $prefix = 'system.logs';

    protected static ?string $segment = 'api';

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view');
    }

    public function view(User $user, ApiCall $apiCall): bool
    {
        return $this->hasPermission($user, 'view');
    }
}
