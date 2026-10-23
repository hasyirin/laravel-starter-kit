<?php

declare(strict_types=1);

namespace App\Domain\User\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;
use App\Domain\User\Models\User;

/**
 * @extends Seeder<int, array{name: string, email: string, roles?: list<string>, permissions?: list<string>}>
 */
class UserSeeder extends Seeder
{
    protected function each(string|int $key, mixed $data): void
    {
        if (User::query()->where('email', $data['email'])->exists()) {
            return;
        }

        $user = User::factory()->create(['name' => $data['name'], 'email' => $data['email']]);

        $user->profiles()->make()
            ->assignRole(...($data['roles'] ?? []))
            ->givePermissionTo(...($data['permissions'] ?? []))
            ->save();
    }

    public function data(): array
    {
        return [
            [
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
                'roles' => [config('system.roles.super-admin')],
            ],
            [
                'name' => 'Admin',
                'email' => 'it@example.com',
            ],
        ];
    }
}
