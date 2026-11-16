<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\Models\Ticket;
use Tests\Fixtures\States\Ticket\InReview;

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

test('the audit log leaves out an unchanged state when another column changes', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    $ticket->update(['title' => 'Flood']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['title' => 'Flood'])
        ->and($changes?->get('old'))->toBe(['title' => 'Leak']);
});

test('the audit log records a state change with the old and the new state names', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    $ticket->state->transitionTo(InReview::class);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['state' => 'in_review'])
        ->and($changes?->get('old'))->toBe(['state' => 'open']);
});

test('a touch writes no audit entry', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    $this->travel(1)->minute();

    $ticket->touch();

    expect(Activity::query()->where('event', 'updated')->exists())->toBeFalse();
});

test('the audit log leaves out the columns that a query did not select when another column changes', function (): void {
    Ticket::query()->create(['title' => 'Leak']);
    $ticket = Ticket::query()->select(['id', 'title'])->sole();

    $ticket->update(['title' => 'Flood']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['title' => 'Flood'])
        ->and($changes?->get('old'))->toBe(['title' => 'Leak']);
});

test('the audit log records a change to a column that the model did not hold, with no old value', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak']);

    $ticket->update(['approvable' => true]);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['approvable' => true])
        ->and($changes?->get('old'))->toBe(['approvable' => null]);
});

test('the audit log records a change that the database makes to a column that the model held, with its old value', function (): void {
    Schema::table('tickets', function (Blueprint $table): void {
        $table->string('code')->virtualAs('upper(title)');
    });
    Ticket::query()->create(['title' => 'Leak']);
    $ticket = Ticket::query()->sole();

    $ticket->update(['title' => 'Flood']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($ticket->getChanges())->not->toHaveKey('code')
        ->and($changes?->get('attributes'))->toBe(['title' => 'Flood', 'code' => 'FLOOD'])
        ->and($changes?->get('old'))->toBe(['title' => 'Leak', 'code' => 'LEAK']);
});
