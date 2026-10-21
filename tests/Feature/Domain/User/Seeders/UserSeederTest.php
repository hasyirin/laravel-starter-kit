<?php

declare(strict_types=1);

use App\Domain\User\Models\User;
use App\Domain\User\Seeders\UserSeeder;

test('seeding creates the admin users', function (): void {
    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('users', ['name' => 'Super Admin', 'email' => 'admin@example.com']);
    $this->assertDatabaseHas('users', ['name' => 'Admin', 'email' => 'it@example.com']);
});

test('seeding skips admins whose email already exists', function (): void {
    User::factory()->create(['name' => 'Existing Admin', 'email' => 'admin@example.com']);

    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('users', ['name' => 'Existing Admin', 'email' => 'admin@example.com']);
    $this->assertDatabaseHas('users', ['name' => 'Admin', 'email' => 'it@example.com']);
});
