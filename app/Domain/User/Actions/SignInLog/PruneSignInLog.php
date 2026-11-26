<?php

declare(strict_types=1);

namespace App\Domain\User\Actions\SignInLog;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\User\Models\SignInEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PruneSignInLog
{
    use Actionable;

    private const int CHUNK_SIZE = 500;

    public function execute(CarbonImmutable $before, int $limit): int
    {
        $archive = fopen('php://temp', 'w+b');
        throw_if($archive === false, RuntimeException::class, 'The sign-in log archive could not be opened.');

        /** @var Collection<int, int> $pruned */
        $pruned = collect();

        SignInEntry::query()
            ->withTrashed()
            ->where('created_at', '<', $before)
            ->chunkById(min(self::CHUNK_SIZE, $limit), function (Collection $entries) use ($archive, $limit, $pruned): bool {
                $entries->take($limit - $pruned->count())->each(function (SignInEntry $entry) use ($archive, $pruned): void {
                    $this->write($archive, $entry);
                    $pruned->push($entry->id);
                });

                return $pruned->count() < $limit;
            });

        if ($pruned->isEmpty()) {
            fclose($archive);

            return 0;
        }

        rewind($archive);
        $path = 'sign-in-log/'.now()->format('Y-m-d-His')."-{$pruned->first()}-{$pruned->last()}.jsonl";
        $written = Storage::disk(config()->string('system.sign_in_log.archive_disk'))->writeStream($path, $archive);
        fclose($archive);

        throw_unless($written, RuntimeException::class, 'The sign-in log archive could not be written.');

        SignInEntry::query()->withTrashed()->whereKey($pruned->all())->forceDelete();

        return $pruned->count();
    }

    /**
     * @param  resource  $archive
     */
    private function write($archive, SignInEntry $entry): void
    {
        $line = json_encode($entry->toArray(), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        throw_unless(fwrite($archive, $line) === strlen($line), RuntimeException::class, 'The sign-in log archive could not be written.');
    }
}
