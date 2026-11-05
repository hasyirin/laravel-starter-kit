<?php

declare(strict_types=1);

namespace Tests\Fixtures\Seeders;

use App\Domain\Shared\Permission\Seeders\RoleSeeder;

class AuditorRoleSeeder extends RoleSeeder
{
    public function data(): array
    {
        return [
            'auditor' => [
                'permissions' => ['internal.access', 'internal.controls.*'],
                'except' => ['internal.controls.*.delete', 'internal.controls.users.update-*'],
            ],
        ];
    }
}
