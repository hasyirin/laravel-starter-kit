<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Policies\Policy;
use App\Domain\Shared\Permission\Models\Role;
use App\Domain\Shared\Permission\Policies\RolePolicy;
use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Policies\ArchivedItemPolicy;
use Tests\Fixtures\Policies\TicketPolicy;
use Tests\Fixtures\States\Ticket\Approved;
use Tests\Fixtures\States\Ticket\InReview;
use Tests\Fixtures\States\Ticket\Returned;

test('the path uses the root, the prefix, the plural model name, and the action', function (): void {
    expect(new RolePolicy()->path('update-permissions'))->toBe('system.permissions.admin.controls.roles.update-permissions');
});

test('the path uses the model property when it is set', function (): void {
    expect(new ArchivedItemPolicy()->path('view'))->toBe('system.permissions.archive.items.view');
});

function rolePermissionsPolicy(): object
{
    return new class extends Policy
    {
        protected static string $prefix = 'admin.controls';

        protected static ?string $model = Role::class;

        public function any(User $user, mixed ...$actions): bool
        {
            return $this->hasAnyPermission($user, ...$actions);
        }

        public function all(User $user, mixed ...$actions): bool
        {
            return $this->hasAllPermissions($user, ...$actions);
        }
    };
}

test('has any permission accepts strings, arrays, and collections', function (mixed $actions): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();

    expect(rolePermissionsPolicy()->any($user, ...$actions))->toBeFalse();

    $user->givePermissionTo('admin.controls.roles.view');

    expect(rolePermissionsPolicy()->any($user, ...$actions))->toBeTrue();
})->with([
    'strings' => [['view-any', 'view']],
    'array' => [[['view-any', 'view']]],
    'collection' => fn (): array => [collect(['view-any', 'view'])],
    'mixed' => fn (): array => ['view-any', collect(['create']), ['view']],
]);

test('has all permissions needs every permission', function (mixed $actions): void {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->givePermissionTo('admin.controls.roles.view');

    expect(rolePermissionsPolicy()->all($user, ...$actions))->toBeFalse();

    $user->givePermissionTo('admin.controls.roles.view-any');

    expect(rolePermissionsPolicy()->all($user, ...$actions))->toBeTrue();
})->with([
    'strings' => [['view-any', 'view']],
    'array' => [[['view-any', 'view']]],
    'collection' => fn (): array => [collect(['view-any', 'view'])],
]);

test('a transition needs the permission of its step', function (bool $reviewed, string $state, string $permission): void {
    Schema::create('tickets', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->string('state');
        $table->boolean('approvable')->default(false);
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Relation::morphMap(['ticket' => Ticket::class]);
    config(['system.permissions.internal.tickets' => TicketPolicy::PERMISSIONS]);
    $this->seed(PermissionSeeder::class);
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    if ($reviewed) {
        $ticket->state->transitionTo(InReview::class);
    }

    $user = User::factory()->create();

    expect($user->can('transition', [$ticket, 'state', $state]))->toBeFalse();

    $user->givePermissionTo($permission);

    expect($user->can('transition', [$ticket, 'state', $state]))->toBeTrue()
        ->and($user->can('transition', [$ticket, 'title', $state]))->toBeFalse();
})->with([
    'a step without a class' => [false, InReview::class, 'internal.tickets.in-review'],
    'a step with a class' => [true, Approved::class, 'internal.tickets.approve'],
    'a step back' => [true, Returned::class, 'internal.tickets.returned'],
]);
