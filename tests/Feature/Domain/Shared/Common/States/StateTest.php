<?php

declare(strict_types=1);

use App\Domain\Shared\Permission\Seeders\PermissionSeeder;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\ModelStates\State;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\Policies\TicketPolicy;
use Tests\Fixtures\States\Ticket\Approved;
use Tests\Fixtures\States\Ticket\InReview;
use Tests\Fixtures\States\Ticket\Open;
use Tests\Fixtures\States\Ticket\Rejected;
use Tests\Fixtures\States\Ticket\Returned;
use Tests\Fixtures\States\Ticket\TicketState;

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
});

test('a new model gets the default state', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    expect($ticket->state)->toBeInstanceOf(Open::class)
        ->and($ticket->getRawOriginal('state'))->toBe('open');
});

test('the label is the headline of the class name, and a state can change it', function (): void {
    expect(new InReview(null)->label())->toBe('In Review')
        ->and(new Rejected(null)->label())->toBe('Turned Down');
});

test('the color is gray, and a state can change it', function (): void {
    expect(new Open(null)->color())->toBe('gray')
        ->and(new Approved(null)->color())->toBe('green');
});

test('the options are the labels of the states by name', function (): void {
    expect(TicketState::options()->all())->toBe([
        'approved' => 'Approved',
        'in_review' => 'In Review',
        'open' => 'Open',
        'rejected' => 'Turned Down',
        'returned' => 'Returned',
    ]);
});

test('is and not compare the state with classes and names', function (): void {
    $state = Ticket::query()->create(['title' => 'Leak'])->state;

    expect($state->is(Open::class))->toBeTrue()
        ->and($state->is('open'))->toBeTrue()
        ->and($state->is(InReview::class, Open::class))->toBeTrue()
        ->and($state->not(InReview::class))->toBeTrue()
        ->and($state->not(Open::class))->toBeFalse();
});

test('the ability of a step is its transition class, or else its target state', function (string $from, string $to, string $ability): void {
    expect(TicketState::abilityTo($from, $to))->toBe($ability);
})->with([
    'a step with a class' => ['in_review', 'approved', 'approve'],
    'a step without a class' => ['open', 'in_review', 'in-review'],
    'state classes instead of names' => [InReview::class, Returned::class, 'returned'],
]);

test('the allowed states pass the guards and the permissions of the user', function (): void {
    config(['system.permissions.internal.tickets' => TicketPolicy::PERMISSIONS]);
    $this->seed(PermissionSeeder::class);
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $ticket->state->transitionTo(InReview::class);

    $reviewer = User::factory()->create();
    $reviewer->givePermissionTo(array_values(TicketPolicy::PERMISSIONS));

    $returner = User::factory()->create();
    $returner->givePermissionTo('internal.tickets.returned');

    $allowed = fn (User $user): array => array_map(fn (State $state): string => $state::getMorphClass(), $ticket->state->allowedFor($user));

    expect($allowed($reviewer))->toBe(['returned', 'rejected'])
        ->and($allowed($returner))->toBe(['returned']);

    $ticket->update(['approvable' => true]);

    expect($allowed($reviewer))->toBe(['returned', 'approved', 'rejected']);
});

test('the reason explains why a step is blocked', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $ticket->state->transitionTo(InReview::class);

    expect($ticket->state->reasonFor(Approved::class))->toBe('The ticket is not ready for approval.')
        ->and($ticket->state->reasonFor(Rejected::class))->toBeNull();

    $ticket->update(['approvable' => true]);

    expect($ticket->state->reasonFor('approved'))->toBeNull();
});

test('the reason of a step that the config does not allow throws', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    expect(fn (): ?string => $ticket->state->reasonFor(Approved::class))->toThrow(TransitionNotFound::class);
});
