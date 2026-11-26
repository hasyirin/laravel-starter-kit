<?php

declare(strict_types=1);

use App\Domain\User\Actions\SignInLog\PruneSignInLog;
use App\Domain\User\Models\SignInEntry;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('pruning archives the old entries with what their rows hold, then deletes them', function (): void {
    Storage::fake('local');
    config(['system.sign_in_log.archive_disk' => 'local']);
    $old = SignInEntry::factory()->create(['created_at' => '2026-01-01 00:00:00', 'ip_address' => '192.0.2.10']);
    $trashed = SignInEntry::factory()->create(['created_at' => '2026-01-02 00:00:00']);
    $trashed->delete();

    $recent = SignInEntry::factory()->create(['created_at' => '2026-03-01 00:00:00']);

    $count = PruneSignInLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 100);

    $lines = str((string) Storage::disk('local')->get(collect(Storage::disk('local')->files('sign-in-log'))->sole()))->trim()->explode("\n");
    $first = json_decode((string) $lines->first(), true);
    expect($count)->toBe(2)
        ->and($lines)->toHaveCount(2)
        ->and(array_keys($first))->toEqualCanonicalizing(Schema::getColumnListing($old->getTable()))
        ->and($first['id'])->toBe($old->id)
        ->and($first['ip_address'])->toBe('192.0.2.10');
    $this->assertDatabaseMissing(SignInEntry::class, ['id' => $old->id]);
    $this->assertDatabaseMissing(SignInEntry::class, ['id' => $trashed->id]);
    $this->assertModelExists($recent);
});

test('pruning stops at the limit', function (): void {
    Storage::fake('local');
    SignInEntry::factory()->count(3)->create(['created_at' => '2026-01-01 00:00:00']);

    $count = PruneSignInLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 2);

    expect($count)->toBe(2);
    $this->assertDatabaseCount(SignInEntry::class, 1);
});

test('pruning without old entries writes no archive', function (): void {
    Storage::fake('local');
    SignInEntry::factory()->create(['created_at' => '2026-03-01 00:00:00']);

    $count = PruneSignInLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 100);

    expect($count)->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('a failed archive write deletes nothing', function (): void {
    config(['system.sign_in_log.archive_disk' => 'archive']);
    $old = SignInEntry::factory()->create(['created_at' => '2026-01-01 00:00:00']);
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('writeStream')->andReturnFalse();
    Storage::set('archive', $disk);

    expect(fn (): int => PruneSignInLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 100))->toThrow(RuntimeException::class);

    $this->assertModelExists($old);
});
