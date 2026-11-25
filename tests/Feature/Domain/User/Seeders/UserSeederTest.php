<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\Shared\Permission\Seeders\RoleSeeder;
use App\Domain\User\Models\User;
use App\Domain\User\Seeders\UserSeeder;
use Faker\Generator;
use Illuminate\Support\Facades\Hash;
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

test('seeding gives each default user a verified email, no phone, and the password password', function (): void {
    $this->seed(UserSeeder::class);

    User::query()->get()->each(function (User $user): void {
        expect($user->email_verified_at)->not->toBeNull()
            ->and($user->phone)->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    });
});

test('seeding works without Faker', function (): void {
    $missing = fn (): never => throw new RuntimeException('Faker is not installed.');
    app()->bind(Generator::class, $missing);
    app()->bind(Generator::class.':'.config('app.faker_locale'), $missing);

    $this->seed(UserSeeder::class);

    $this->assertDatabaseCount('users', 2);
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
