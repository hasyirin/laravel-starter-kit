<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Actions\DeleteRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    $this->role = Role::query()->create(['name' => 'editor']);
});

test('a role is deleted', function (): void {
    DeleteRole::make()->execute($this->role, $this->actor);

    expect(Role::query()->whereKey($this->role->id)->exists())->toBeFalse();
});

test('a role with bypass-authorization cannot be deleted without bypass', function (): void {
    $this->role->givePermissionTo('bypass-authorization');

    expect(fn () => DeleteRole::make()->execute($this->role, $this->actor))->toThrow(AuthorizationException::class)
        ->and(Role::query()->whereKey($this->role->id)->exists())->toBeTrue();
});
