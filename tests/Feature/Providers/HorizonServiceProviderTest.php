<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;

test('a user with the Horizon view permission can view Horizon', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('admin.horizon.view');

    expect($user->can('viewHorizon'))->toBeTrue();
});

test('a user without the Horizon view permission cannot view Horizon', function (): void {
    $this->seed(PermissionSeeder::class);

    expect(User::factory()->create()->can('viewHorizon'))->toBeFalse();
});

test('the dashboard opens for a user with the Horizon view permission', function (): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('admin.horizon.view');

    $this->actingAs($user)->get(route('horizon.index'))->assertOk();
});

test('the dashboard is forbidden for a guest', function (): void {
    $this->get(route('horizon.index'))->assertForbidden();
});
