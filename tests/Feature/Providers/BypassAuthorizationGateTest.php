<?php

declare(strict_types=1);

use App\Domain\Shared\Api\Models\ApiClient;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

test('a user with the bypass-authorization permission and no role passes every ability', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    grantPermissions($user, 'bypass-authorization');

    expect($user->roles)->toBeEmpty()
        ->and($user->can('anything'))->toBeTrue();
});

test('a user without the bypass-authorization permission does not pass every ability', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    grantRoles($user, Role::query()->create(['name' => 'editor']));

    expect($user->can('anything'))->toBeFalse()
        ->and($user->can('system.access'))->toBeFalse();
});

test('a user with a role gets the permissions of that role', function (): void {
    $this->seed(PermissionSeeder::class);

    $role = Role::query()->create(['name' => 'editor']);
    $role->givePermissionTo('system.access');

    $user = User::factory()->create();
    grantRoles($user, $role);

    expect($user->can('system.access'))->toBeTrue()
        ->and($user->can('internal.controls.users.delete'))->toBeFalse();
});

test('ability checks do not fail when the bypass-authorization permission does not exist', function (): void {
    expect(User::factory()->create()->can('anything'))->toBeFalse();
});

test('the bypass gives an API client no decision, so a stray authorize in an API route returns 403', function (): void {
    $this->seed(PermissionSeeder::class);
    Route::middleware('api')->prefix('api/v1')->get('stray', function (): array {
        Gate::authorize('system.access');

        return ['passed' => true];
    });

    $response = $this->withToken(createApiToken())->getJson('/api/v1/stray');

    $response->assertForbidden();

    expect(Gate::forUser(ApiClient::factory()->create())->allows('system.access'))->toBeFalse();
});
