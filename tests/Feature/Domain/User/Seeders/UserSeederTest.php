<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Seeders\UserSeeder;
use Tests\Fixtures\Seeders\AuditorUserSeeder;

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('seeding creates the default users', function (): void {
    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('users', ['name' => 'Super Admin', 'email' => 'admin@example.com']);
    $this->assertDatabaseHas('users', ['name' => 'Admin', 'email' => 'it@example.com']);
});

test('seeding skips a default user whose email already exists', function (): void {
    User::factory()->create(['name' => 'Existing User', 'email' => 'admin@example.com']);

    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseHas('users', ['name' => 'Existing User', 'email' => 'admin@example.com']);
    $this->assertDatabaseHas('users', ['name' => 'Admin', 'email' => 'it@example.com']);
    expect(User::query()->where('email', 'admin@example.com')->sole()->profile)->toBeNull();
});

test('seeding the database with model events off gives admin@example.com the system-admin role', function (): void {
    $this->seed();

    expect(User::query()->where('email', 'admin@example.com')->sole()->hasRole('system-admin'))->toBeTrue()
        ->and(User::query()->where('email', 'it@example.com')->sole()->hasRole('system-admin'))->toBeFalse();
});

test('seeding gives a user the permissions in their data', function (): void {
    $this->seed(AuditorUserSeeder::class);

    expect(User::query()->where('email', 'auditor@example.com')->sole()->profile?->getPermissionNames()->sort()->values()->all())
        ->toBe(['system.access', 'system.horizon.view']);
});
