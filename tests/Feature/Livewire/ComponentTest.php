<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\AbilityCheck;
use Tests\Fixtures\Livewire\TicketSteps;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Policies\TicketPolicy;
use Tests\Fixtures\States\Ticket\Approved;
use Tests\Fixtures\States\Ticket\InReview;
use Tests\Fixtures\States\Ticket\Returned;

beforeEach(function (): void {
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

    $this->ticket = Ticket::query()->create(['title' => 'Leak']);
    $this->ticket->state->transitionTo(InReview::class);

    $reviewer = User::factory()->create();
    $reviewer->givePermissionTo(array_values(TicketPolicy::PERMISSIONS));
    $this->actingAs($reviewer);
});

test('the ability helpers check the permissions of the current user', function (array $permissions, array $results): void {
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
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(AbilityCheck::class);

    expect(fn () => $component->call($method, 'admin.access'))->toThrow(MethodNotFoundException::class);
})->with(['can', 'cannot', 'canAny', 'cannotAny']);

test('a step runs when the record still has it', function (): void {
    Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id])
        ->call('move', Returned::class)
        ->assertSet('ran', true)
        ->assertNotDispatched('toast-show')
        ->assertNoRedirect();

    expect($this->ticket->fresh()?->state)->toBeInstanceOf(Returned::class);
});

test('a step that the record no longer has shows a toast and reloads the page', function (): void {
    $page = url('/internal/tickets/1?tab=history');
    session()->setPreviousUrl($page);
    $component = Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id]);
    $this->ticket->state->transitionTo(Returned::class);

    $component->call('move', Approved::class)
        ->assertSet('ran', false)
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('common/workflows.changed'))
        ->assertRedirect($page);
});

test('a step that another request saves first shows a toast and reloads the page', function (): void {
    $component = Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id]);
    $changed = false;
    Ticket::retrieved(function () use (&$changed): void {
        if (! $changed) {
            $changed = true;
            DB::table('tickets')->update(['state' => 'rejected']);
        }
    });

    $component->call('move', Returned::class)
        ->assertSet('ran', false)
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('common/workflows.changed'))
        ->assertRedirect();
});

test('a step that its reason blocks shows the reason', function (): void {
    Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id])
        ->call('move', Approved::class)
        ->assertSet('ran', false)
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === 'The ticket is not ready for approval.')
        ->assertNoRedirect();
});

test('a step whose save a listener stops shows a toast', function (): void {
    Ticket::saving(fn (): bool => false);

    Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id])
        ->call('move', Returned::class)
        ->assertSet('ran', false)
        ->assertDispatched('toast-show', fn (string $name, array $params): bool => $params['slots']['text'] === __('common/workflows.not_saved'))
        ->assertNoRedirect();
});

test('a step without the permission is forbidden', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id])
        ->call('move', Returned::class)
        ->assertForbidden();
});

test('the step helper cannot be called from the browser', function (): void {
    $component = Livewire::test(TicketSteps::class, ['ticketId' => $this->ticket->id]);

    expect(fn () => $component->call('transitionTo', 'in_review', 'returned'))->toThrow(MethodNotFoundException::class);
});
