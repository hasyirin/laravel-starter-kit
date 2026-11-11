<?php

declare(strict_types=1);

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Media\Enums\MediaCollection;
use App\Domain\Shared\Message\Actions\PruneMessageLog;
use App\Domain\Shared\Message\Models\Message;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

test('pruning archives the old emails without their secrets, then deletes them with their attachments', function (): void {
    Storage::fake('local');
    config(['system.message_log.archive_disk' => 'local']);
    $old = Message::factory()->create(['created_at' => '2026-01-01 00:00:00', 'payload' => 'serialized']);
    $old->addMediaFromString('letter content')->usingFileName('letter.pdf')->toMediaCollection(MediaCollection::Attachments->value);
    $path = $old->getFirstMedia(MediaCollection::Attachments)?->getPath();
    $recent = Message::factory()->create(['created_at' => '2026-03-01 00:00:00']);

    $count = PruneMessageLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 100);

    $archive = Storage::disk('local')->get(collect(Storage::disk('local')->files('message-log'))->sole());
    $line = json_decode((string) $archive, true);
    expect($count)->toBe(1)
        ->and($line['id'])->toBe($old->id)
        ->and($line)->not->toHaveKeys(['payload', 'tracking_token'])
        ->and(file_exists((string) $path))->toBeFalse();
    $this->assertModelMissing($old);
    $this->assertModelExists($recent);
});

test('pruning stops at the limit', function (): void {
    Storage::fake('local');
    Message::factory()->count(3)->create(['created_at' => '2026-01-01 00:00:00']);

    $count = PruneMessageLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 2);

    expect($count)->toBe(2);
    $this->assertDatabaseCount(Message::class, 1);
});

test('pruning deletes the preparer of each pruned email', function (): void {
    Storage::fake('local');
    $this->actingAs(User::factory()->create());
    $old = Message::factory()->create(['created_at' => '2026-01-01 00:00:00']);
    $recent = Message::factory()->create(['created_at' => '2026-03-01 00:00:00']);

    PruneMessageLog::make()->execute(CarbonImmutable::parse('2026-02-01 00:00:00'), 100);

    $this->assertDatabaseMissing(Action::class, ['resource_type' => $old->getMorphClass(), 'resource_id' => $old->id]);
    $this->assertDatabaseHas(Action::class, ['resource_type' => $recent->getMorphClass(), 'resource_id' => $recent->id]);
});
