<?php

declare(strict_types=1);

namespace App\Domain\Shared\Api\Actions;

use App\Domain\Shared\Api\Models\ApiCall;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PruneApiLog
{
    use Actionable;

    private const int CHUNK_SIZE = 500;

    public function execute(CarbonImmutable $before, int $limit): int
    {
        $archive = fopen('php://temp', 'w+b');
        throw_if($archive === false, RuntimeException::class, 'The API log archive could not be opened.');

        /** @var Collection<int, int> $pruned */
        $pruned = collect();

        ApiCall::query()
            ->withTrashed()
            ->where('created_at', '<', $before)
            ->chunkById(min(self::CHUNK_SIZE, $limit), function (Collection $apiCalls) use ($archive, $limit, $pruned): bool {
                $apiCalls->take($limit - $pruned->count())->each(function (ApiCall $apiCall) use ($archive, $pruned): void {
                    $this->write($archive, $apiCall);
                    $pruned->push($apiCall->id);
                });

                return $pruned->count() < $limit;
            });

        if ($pruned->isEmpty()) {
            fclose($archive);

            return 0;
        }

        rewind($archive);
        $path = 'api-log/'.now()->format('Y-m-d-His')."-{$pruned->first()}-{$pruned->last()}.jsonl";
        $written = Storage::disk(config()->string('system.api_log.archive_disk'))->writeStream($path, $archive);
        fclose($archive);

        throw_unless($written, RuntimeException::class, 'The API log archive could not be written.');

        ApiCall::query()->withTrashed()->whereKey($pruned->all())->forceDelete();

        return $pruned->count();
    }

    /**
     * @param  resource  $archive
     */
    private function write($archive, ApiCall $apiCall): void
    {
        $line = json_encode($apiCall->toArray(), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        throw_unless(fwrite($archive, $line) === strlen($line), RuntimeException::class, 'The API log archive could not be written.');
    }
}
