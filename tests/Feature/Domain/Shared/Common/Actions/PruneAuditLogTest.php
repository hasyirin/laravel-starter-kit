<?php

declare(strict_types=1);

use App\Domain\Shared\Common\Actions\PruneAuditLog;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    config(['system.audit.archive_disk' => 'archive']);
    Storage::fake('archive');
});

function auditEntryAt(string $time): Activity
{
    test()->travelTo(CarbonImmutable::parse($time));
    User::factory()->create();

    return Activity::query()->latest('id')->firstOrFail();
}

test('entries older than the cutoff go into an archive file and leave the log', function (): void {
    $old = auditEntryAt('2025-01-01 10:00');
    $kept = auditEntryAt('2026-01-01 10:00');
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));

    $pruned = PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1000);

    expect($pruned)->toBe(1);
    $this->assertModelMissing($old);
    $this->assertModelExists($kept);
    $path = "audit-log/2026-06-01-000000-{$old->id}-{$old->id}.jsonl";
    Storage::disk('archive')->assertExists($path);

    $lines = collect(explode("\n", trim((string) Storage::disk('archive')->get($path))))
        ->map(fn (string $line): mixed => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

    expect($lines)->toHaveCount(1)
        ->and($lines->first())->toMatchArray(['id' => $old->id, 'event' => 'created', 'subject_type' => 'user', 'subject_id' => $old->subject_id])
        ->and($lines->first()['attribute_changes']['attributes'])->toHaveKey('name');
});

test('no entry older than the cutoff writes no file and deletes nothing', function (): void {
    $kept = auditEntryAt('2026-01-01 10:00');

    $pruned = PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1000);

    expect($pruned)->toBe(0)
        ->and(Storage::disk('archive')->allFiles())->toBeEmpty();
    $this->assertModelExists($kept);
});

test('a failed archive write deletes nothing', function (): void {
    $old = auditEntryAt('2025-01-01 10:00');
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('writeStream')->andReturnFalse();
    Storage::set('archive', $disk);

    expect(fn (): int => PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1000))->toThrow(RuntimeException::class);

    $this->assertModelExists($old);
});

test('a run archives and deletes at most the limit, oldest first', function (): void {
    $oldest = auditEntryAt('2025-01-01 10:00');
    $older = auditEntryAt('2025-01-02 10:00');
    $old = auditEntryAt('2025-01-03 10:00');

    $pruned = PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 2);

    expect($pruned)->toBe(2);
    $this->assertModelMissing($oldest);
    $this->assertModelMissing($older);
    $this->assertModelExists($old);
    expect(Storage::disk('archive')->get(Storage::disk('archive')->allFiles()[0]))->toContain('"id":'.$oldest->id)->not->toContain('"id":'.$old->id);
});

test('two runs in the same second write two files', function (): void {
    $first = auditEntryAt('2025-01-01 10:00');
    $second = auditEntryAt('2025-01-02 10:00');
    $this->travelTo(CarbonImmutable::parse('2026-06-01 00:00:00'));

    PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1);
    PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1);

    expect(Storage::disk('archive')->allFiles())->toBe([
        "audit-log/2026-06-01-000000-{$first->id}-{$first->id}.jsonl",
        "audit-log/2026-06-01-000000-{$second->id}-{$second->id}.jsonl",
    ]);
});

test('a value that is not valid UTF-8 is archived with a replacement character', function (): void {
    $this->travelTo(CarbonImmutable::parse('2025-01-01 10:00'));
    $entry = Activity::query()->create(['description' => "bad \xB1 byte", 'event' => 'updated']);

    PruneAuditLog::make()->execute(CarbonImmutable::parse('2025-06-01'), 1000);

    $this->assertModelMissing($entry);
    expect(Storage::disk('archive')->get(Storage::disk('archive')->allFiles()[0]))->toContain('bad \\ufffd byte');
});
