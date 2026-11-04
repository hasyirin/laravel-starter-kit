<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Actions\CreateRole;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->actor = User::factory()->create();
    $this->actingAs($this->actor);
});

test('a new role gets the web guard and its permissions', function (): void {
    $role = CreateRole::make()->execute($this->actor, 'editor', ['admin.access', 'admin.controls.users.view']);

    expect($role->fresh()?->guard_name)->toBe('web')
        ->and($role->getPermissionNames()->sort()->values()->all())->toBe(['admin.access', 'admin.controls.users.view']);
});

test('a new role is logged once with its permissions', function (): void {
    $role = CreateRole::make()->execute($this->actor, 'editor', ['admin.controls.users.view', 'admin.access']);

    $entry = Activity::query()->where('subject_type', 'role')->sole();

    expect($entry->event)->toBe('created')
        ->and($entry->subject?->is($role))->toBeTrue()
        ->and($entry->causer?->is($this->actor))->toBeTrue()
        ->and($entry->attribute_changes?->get('attributes'))->toMatchArray([
            'name' => 'editor',
            'permissions' => ['admin.access', 'admin.controls.users.view'],
        ]);
});

test('bypass-authorization cannot be given without bypass', function (): void {
    expect(fn (): Role => CreateRole::make()->execute($this->actor, 'owner', ['bypass-authorization']))->toThrow(AuthorizationException::class);
});

test('bypass-authorization can be given with bypass', function (): void {
    $this->actor->givePermissionTo('bypass-authorization');

    $role = CreateRole::make()->execute($this->actor, 'owner', ['bypass-authorization']);

    expect($role->hasPermissionTo('bypass-authorization'))->toBeTrue();
});
