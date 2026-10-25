<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\AbilityCheckFormHost;

test('the ability helpers check the permissions of the current user', function (array $permissions, array $results): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(...$permissions);
    $this->actingAs($user);

    $component = Livewire::test(AbilityCheckFormHost::class);

    $component->assertSet('form.results', $results);
})->with([
    'with admin access' => [['admin.access'], ['can' => true, 'cannot' => false, 'canAny' => true, 'cannotAny' => false]],
    'with Horizon access only' => [['admin.horizon.view'], ['can' => false, 'cannot' => true, 'canAny' => true, 'cannotAny' => false]],
    'without permissions' => [[], ['can' => false, 'cannot' => true, 'canAny' => false, 'cannotAny' => true]],
]);

test('authorize checks the permissions of the current user', function (): void {
    $this->seed(PermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('admin.horizon.view');
    $this->actingAs($user);

    $form = Livewire::test(AbilityCheckFormHost::class)->instance()->form;

    expect($form->authorize('admin.horizon.view')->allowed())->toBeTrue()
        ->and(fn () => $form->authorize('admin.access'))->toThrow(AuthorizationException::class);
});
