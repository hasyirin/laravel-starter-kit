<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\AbilityCheck;

test('the ability helpers check the permissions of the current user', function (array $permissions, array $results): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(...$permissions);
    $this->actingAs($user);

    $component = Livewire::test(AbilityCheck::class);

    $component->assertSet('results', $results);
})->with([
    'with admin access' => [['admin.access'], ['can' => true, 'cannot' => false, 'canAny' => true, 'cannotAny' => false]],
    'with Horizon access only' => [['admin.horizon.view'], ['can' => false, 'cannot' => true, 'canAny' => true, 'cannotAny' => false]],
    'without permissions' => [[], ['can' => false, 'cannot' => true, 'canAny' => false, 'cannotAny' => true]],
]);

test('the ability helpers cannot be called from the browser', function (string $method): void {
    $this->seed(PermissionSeeder::class);
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(AbilityCheck::class);

    expect(fn () => $component->call($method, 'admin.access'))->toThrow(MethodNotFoundException::class);
})->with(['can', 'cannot', 'canAny', 'cannotAny']);
