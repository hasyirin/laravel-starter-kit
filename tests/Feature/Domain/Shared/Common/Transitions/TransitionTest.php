<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Events\StateChanged;
use App\Domain\Shared\Common\Exceptions\StaleState;
use App\Domain\Shared\Common\Exceptions\StateNotSaved;
use Illuminate\Database\Eloquent\Relations\Relation;
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

test('a step fails when another request changed the state first', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $stale = Ticket::query()->findOrFail($ticket->id);

    $ticket->state->transitionTo(InReview::class);

    expect(fn (): mixed => $stale->state->transitionTo(InReview::class))->toThrow(StaleState::class)
        ->and($ticket->fresh()?->state)->toBeInstanceOf(InReview::class);
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
