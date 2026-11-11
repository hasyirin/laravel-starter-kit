<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Common\Models\AuditEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class PruneAuditLog
{
    use Actionable;

    private const int CHUNK_SIZE = 1000;

    public function execute(CarbonImmutable $before, int $limit): int
    {
        $archive = fopen('php://temp', 'w+b');
        throw_if($archive === false, RuntimeException::class, 'The audit log archive could not be opened.');

        $firstId = null;
        $lastId = null;
        $count = 0;

        Activity::query()
            ->where('created_at', '<', $before)
            ->chunkById(min(self::CHUNK_SIZE, $limit), function (Collection $entries) use ($archive, $limit, &$firstId, &$lastId, &$count): bool {
                $entries = $entries->take($limit - $count);
                $entries->each(fn (Activity $entry) => $this->write($archive, $entry));

                $ids = $entries->map(fn (Activity $entry): int => $entry->id);
                $firstId ??= $ids->first();
                $lastId = $ids->last();
                $count += $ids->count();

                return $count < $limit;
            });

        if ($count === 0) {
            fclose($archive);

            return 0;
        }

        rewind($archive);
        $path = 'audit-log/'.now()->format('Y-m-d-His')."-{$firstId}-{$lastId}.jsonl";
        $written = Storage::disk(config()->string('system.audit.archive_disk'))->writeStream($path, $archive);
        fclose($archive);

        throw_unless($written, RuntimeException::class, 'The audit log archive could not be written.');

        AuditEntry::query()
            ->where('created_at', '<', $before)
            ->where('id', '<=', $lastId)
            ->whereHas('media')
            ->with('media')
            ->chunkById(self::CHUNK_SIZE, fn (Collection $entries) => $entries->each(fn (AuditEntry $entry) => $entry->media->each->delete()));

        Activity::query()
            ->where('created_at', '<', $before)
            ->where('id', '<=', $lastId)
            ->delete();

        return $count;
    }

    /**
     * @param  resource  $archive
     */
    private function write($archive, Activity $entry): void
    {
        $line = json_encode($entry->toArray(), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        throw_unless(fwrite($archive, $line) === strlen($line), RuntimeException::class, 'The audit log archive could not be written.');
    }
}
