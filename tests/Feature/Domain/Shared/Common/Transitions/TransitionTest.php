<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Events\StateChanged;
use App\Domain\Shared\Common\Exceptions\StaleState;
use App\Domain\Shared\Common\Exceptions\StateNotSaved;
use App\Domain\Shared\Common\Exceptions\TransitionBlocked;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\ModelStates\Exceptions\TransitionNotAllowed;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\States\Ticket\Approved;
use Tests\Fixtures\States\Ticket\InReview;
use Tests\Fixtures\States\Ticket\Open;

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

test('a step without its own class saves the new state', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    $ticket->state->transitionTo(InReview::class);

    expect($ticket->fresh()?->state)->toBeInstanceOf(InReview::class)
        ->and(DB::table('tickets')->value('state'))->toBe('in_review');
});

test('a transition class saves its other changes with the new state', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak', 'approvable' => true]);
    $ticket->state->transitionTo(InReview::class);

    $ticket->state->transitionTo(Approved::class);

    expect($ticket->fresh()?->state)->toBeInstanceOf(Approved::class)
        ->and($ticket->fresh()?->approved_at)->not->toBeNull();
});

test('a step that its reason blocks throws and keeps the state', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $ticket->state->transitionTo(InReview::class);

    expect(fn (): mixed => $ticket->state->transitionTo(Approved::class))->toThrow(TransitionNotAllowed::class)
        ->and($ticket->fresh()?->state)->toBeInstanceOf(InReview::class);
});

test('a step that its reason blocks throws the reason', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $ticket->state->transitionTo(InReview::class);

    try {
        $ticket->state->transitionTo(Approved::class);
        $this->fail('The step was not blocked.');
    } catch (TransitionBlocked $transitionBlocked) {
        expect($transitionBlocked->reason)->toBe('The ticket is not ready for approval.')
            ->and($transitionBlocked)->toBeInstanceOf(TransitionNotAllowed::class);
    }
});

test('a step fails when another request changed the state first', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $stale = Ticket::query()->findOrFail($ticket->id);

    $ticket->state->transitionTo(InReview::class);

    expect(fn (): mixed => $stale->state->transitionTo(InReview::class))->toThrow(StaleState::class)
        ->and($ticket->fresh()?->state)->toBeInstanceOf(InReview::class);
});

test('a step fails when another request deleted the record first', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    DB::table('tickets')->delete();

    expect(fn (): mixed => $ticket->state->transitionTo(InReview::class))->toThrow(StaleState::class);
});

test('a failed step leaves the model as it was', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak', 'approvable' => true]);
    $ticket->state->transitionTo(InReview::class);
    Ticket::saving(fn (): bool => false);

    expect(fn (): mixed => $ticket->state->transitionTo(Approved::class))->toThrow(StateNotSaved::class)
        ->and($ticket->state)->toBeInstanceOf(InReview::class)
        ->and($ticket->isDirty())->toBeFalse();
});

test('a step passes its arguments to the transition class', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak', 'approvable' => true]);
    $ticket->state->transitionTo(InReview::class);

    $ticket->state->transitionTo(Approved::class, CarbonImmutable::parse('2026-01-02 03:04:05'));

    expect($ticket->fresh()?->approved_at?->toDateTimeString())->toBe('2026-01-02 03:04:05');
});

test('a step on a new model saves the model', function (): void {
    $ticket = new Ticket(['title' => 'Leak']);

    $ticket->state->transitionTo(InReview::class);

    expect($ticket->exists)->toBeTrue()
        ->and(DB::table('tickets')->value('state'))->toBe('in_review');
});

test('a step whose save a listener stops throws and sends no event', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $events = 0;
    Event::listen(StateChanged::class, function () use (&$events): void {
        $events++;
    });
    Ticket::saving(fn (): bool => false);

    expect(fn (): mixed => $ticket->state->transitionTo(InReview::class))->toThrow(StateNotSaved::class)
        ->and(DB::table('tickets')->value('state'))->toBe('open')
        ->and($events)->toBe(0);
});

test('a step runs its transaction on the connection of its model', function (): void {
    config(['database.connections.secondary' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
    Schema::connection('secondary')->create('tickets', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->string('state');
        $table->boolean('approvable')->default(false);
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    $ticket = Ticket::on('secondary')->create(['title' => 'Leak']);
    $connections = [];
    Event::listen(TransactionBeginning::class, function (TransactionBeginning $event) use (&$connections): void {
        $connections[] = $event->connectionName;
    });

    $ticket->state->transitionTo(InReview::class);

    expect($connections)->toBe(['secondary'])
        ->and(DB::connection('secondary')->table('tickets')->value('state'))->toBe('in_review');
});

test('the state changed event waits for the transaction to commit', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $events = [];
    Event::listen(StateChanged::class, function (StateChanged $event) use (&$events): void {
        $events[] = $event;
    });

    DB::transaction(function () use ($ticket, &$events): void {
        $ticket->state->transitionTo(InReview::class);

        expect($events)->toBeEmpty();
    });

    expect($events)->toHaveCount(1)
        ->and($events[0]->initialState)->toBeInstanceOf(Open::class)
        ->and($events[0]->finalState)->toBeInstanceOf(InReview::class)
        ->and($events[0]->field)->toBe('state');
});
