<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\User\Models\SignInEntry;
use Illuminate\Support\Facades\Schema;

test('the sign-in log is a log of its own, so its entries stay out of the audit log', function (): void {
    $entry = SignInEntry::factory()->create();

    $entry->delete();

    expect(AuditEntry::query()->where('subject_type', 'sign_in_entry')->exists())->toBeFalse();
});

test('an entry keeps its user and its impersonator after they are deleted', function (): void {
    $entry = SignInEntry::factory()->impersonationStarted()->create();
    $user = $entry->user;
    $impersonator = $entry->impersonator;
    $user?->delete();
    $impersonator?->delete();

    $fresh = $entry->fresh();

    expect($fresh?->user?->is($user))->toBeTrue()
        ->and($fresh?->impersonator?->is($impersonator))->toBeTrue();
});

test('the sign-in log keeps only its known columns, so it has no column for an email address', function (): void {
    expect(Schema::getColumnListing(new SignInEntry()->getTable()))->toEqualCanonicalizing([
        'id',
        'event',
        'result',
        'method',
        'two_factor',
        'user_id',
        'impersonator_id',
        'ip_address',
        'user_agent',
        'trace_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);
});
