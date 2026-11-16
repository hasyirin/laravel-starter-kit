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
    $ticket = Ticket::query()->create(['title' => 'Leak'])->refresh();

    $ticket->update(['title' => 'Flood']);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['title' => 'Flood'])
        ->and($changes?->get('old'))->toBe(['title' => 'Leak']);
});

test('the audit log records a state change with the old and the new state names', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak'])->refresh();

    $ticket->state->transitionTo(InReview::class);

    $changes = Activity::query()->where('event', 'updated')->sole()->attribute_changes;

    expect($changes?->get('attributes'))->toBe(['state' => 'in_review'])
        ->and($changes?->get('old'))->toBe(['state' => 'open']);
});

test('a touch writes no audit entry', function (): void {
    $ticket = Ticket::query()->create(['title' => 'Leak'])->refresh();
    $this->travel(1)->minute();

    $ticket->touch();

    expect(Activity::query()->where('event', 'updated')->exists())->toBeFalse();
});
