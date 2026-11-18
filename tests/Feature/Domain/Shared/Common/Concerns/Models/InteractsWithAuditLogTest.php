<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\Models\Survey;
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

    Schema::create('surveys', function (Blueprint $table): void {
        $table->id();
        $table->uuid()->unique();
        $table->string('title');
        $table->timestamps();
        $table->softDeletes();
    });

    Relation::morphMap(['ticket' => Ticket::class, 'survey' => Survey::class]);
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

test('the audit log leaves out a unique id column beside the integer key', function (): void {
    Survey::query()->create(['title' => 'Customer feedback']);

    $changes = Activity::query()->where('event', 'created')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['title' => 'Customer feedback']);
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

test('the audit log records a change that an observer makes to a column that the model did not hold, with no old value', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00', 'UTC'));
    $ticket = Ticket::query()->create(['title' => 'Leak']);
    // Listen after the model boots, so this listener runs after the trait listeners, as an #[ObservedBy] observer does.
    Ticket::updating(function (Ticket $ticket): void {
        $ticket->approved_at = now();
    });

    $ticket->update(['title' => 'Flood']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['title' => 'Flood', 'approved_at' => '2026-11-17T10:00:00.000000Z'])
        ->and($changes?->get('old'))->toBe(['title' => 'Leak', 'approved_at' => null]);
});
