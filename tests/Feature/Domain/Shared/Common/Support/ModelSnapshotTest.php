<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Support\ModelSnapshot;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Models\Ticket;

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

function saveTitle(Ticket $ticket, string $title): void
{
    DB::transaction(function () use ($ticket, $title): void {
        ModelSnapshot::restoreOnRollBack($ticket);

        $ticket->title = $title;
        $ticket->save();
    });
}

function failAfter(Closure $steps): void
{
    expect(fn (): mixed => DB::transaction(function () use ($steps): void {
        $steps();

        throw new RuntimeException('A later step failed.');
    }))->toThrow(RuntimeException::class);
}

test('two saves that an outer transaction rolls back restore the first snapshot', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);

    failAfter(function () use ($ticket): void {
        saveTitle($ticket, 'Second');
        saveTitle($ticket, 'Third');
    });

    expect($ticket->title)->toBe('Start')
        ->and($ticket->isDirty())->toBeFalse()
        ->and($ticket->fresh()?->title)->toBe('Start');
});

test('a later snapshot that rolls back first does not win', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);

    failAfter(function () use ($ticket): void {
        saveTitle($ticket, 'Second');
        DB::transaction(fn () => saveTitle($ticket, 'Third'));
    });

    expect($ticket->title)->toBe('Start')
        ->and($ticket->fresh()?->title)->toBe('Start');
});

test('a savepoint that rolls back restores only its own snapshot', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);

    failAfter(function () use ($ticket): void {
        saveTitle($ticket, 'Second');
        failAfter(fn () => saveTitle($ticket, 'Third'));

        expect($ticket->title)->toBe('Second')
            ->and($ticket->fresh()?->title)->toBe('Second');

        saveTitle($ticket, 'Fourth');
    });

    expect($ticket->title)->toBe('Start')
        ->and($ticket->fresh()?->title)->toBe('Start');
});

test('a snapshot of a committed save does not block a later restore', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);
    saveTitle($ticket, 'Second');

    failAfter(fn () => saveTitle($ticket, 'Third'));

    expect($ticket->title)->toBe('Second')
        ->and($ticket->fresh()?->title)->toBe('Second');
});

test('a nested deadlock restores the first snapshot', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);

    expect(fn (): mixed => DB::transaction(function () use ($ticket): void {
        saveTitle($ticket, 'Second');

        DB::transaction(function () use ($ticket): void {
            saveTitle($ticket, 'Third');

            throw new PDOException('Deadlock found when trying to get lock');
        });
    }))->toThrow(DeadlockException::class);

    expect($ticket->title)->toBe('Start')
        ->and($ticket->fresh()?->title)->toBe('Start');
});

test('an unsaved change made before the first snapshot stays after a rollback', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Start']);
    $ticket->approvable = true;

    failAfter(fn () => saveTitle($ticket, 'Second'));

    expect($ticket->title)->toBe('Start')
        ->and($ticket->approvable)->toBeTrue()
        ->and($ticket->getDirty())->toBe(['approvable' => true]);
});

test('a new model is new again after its insert rolls back', function (): void {
    $ticket = new Ticket(['title' => 'Start']);

    failAfter(function () use ($ticket): void {
        saveTitle($ticket, 'Second');
        saveTitle($ticket, 'Third');
    });

    expect($ticket->exists)->toBeFalse()
        ->and($ticket->wasRecentlyCreated)->toBeFalse()
        ->and($ticket->id)->toBeNull()
        ->and($ticket->title)->toBe('Start')
        ->and(Ticket::query()->count())->toBe(0);
});
