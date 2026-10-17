<?php

namespace App\Domain\User\Seeders;

use App\Domain\Shared\Common\Seeders\Seeder;
use App\Domain\User\Models\User;

/**
 * @extends Seeder<int, array{name: string, email: string}>
 */
class UserSeeder extends Seeder
{
    protected function each(string|int $key, mixed $data): void
    {
        if (User::query()->where('email', $data['email'])->exists()) {
            return;
        }

        User::factory()->create($data);
    }

    public function data(): array
    {
        return [
            [
                'name' => 'Super Admin',
                'email' => 'admin@example.com',
            ],
            [
                'name' => 'Admin',
                'email' => 'it@example.com',
            ],
        ];
    }
}
