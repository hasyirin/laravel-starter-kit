<?php

declare(strict_types=1);

namespace Tests\Fixtures\Seeders;

use App\Domain\User\Seeders\UserSeeder;

class AuditorUserSeeder extends UserSeeder
{
    public function data(): array
    {
        return [
            [
                'name' => 'Auditor',
                'email' => 'auditor@example.com',
                'permissions' => ['system.access', 'system.horizon.view'],
            ],
        ];
    }
}
