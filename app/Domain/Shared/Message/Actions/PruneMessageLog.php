<?php

declare(strict_types=1);

namespace App\Domain\Shared\Message\Actions;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Message\Models\Message;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PruneMessageLog
{
    use Actionable;

    private const int CHUNK_SIZE = 500;

    public function execute(CarbonImmutable $before, int $limit): int
    {
        $archive = fopen('php://temp', 'w+b');
        throw_if($archive === false, RuntimeException::class, 'The message log archive could not be opened.');

        /** @var Collection<int, int> $pruned */
        $pruned = collect();

        Message::query()
            ->withTrashed()
            ->where('created_at', '<', $before)
            ->chunkById(min(self::CHUNK_SIZE, $limit), function (Collection $messages) use ($archive, $limit, $pruned): bool {
                $messages->take($limit - $pruned->count())->each(function (Message $message) use ($archive, $pruned): void {
                    $this->write($archive, $message);
                    $pruned->push($message->id);
                });

                return $pruned->count() < $limit;
            });

        if ($pruned->isEmpty()) {
            fclose($archive);

            return 0;
        }

        rewind($archive);
        $path = 'message-log/'.now()->format('Y-m-d-His')."-{$pruned->first()}-{$pruned->last()}.jsonl";
        $written = Storage::disk(config()->string('system.message_log.archive_disk'))->writeStream($path, $archive);
        fclose($archive);

        throw_unless($written, RuntimeException::class, 'The message log archive could not be written.');

        // A force delete on each model also deletes its attachment copies.
        Message::query()->withTrashed()->whereKey($pruned->all())->lazyById()->each(fn (Message $message): ?bool => $message->forceDelete());
        Action::query()->where('resource_type', new Message()->getMorphClass())->whereIn('resource_id', $pruned->all())->delete();

        return $pruned->count();
    }

    /**
     * @param  resource  $archive
     */
    private function write($archive, Message $message): void
    {
        $line = json_encode($message->toArray(), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n";

        throw_unless(fwrite($archive, $line) === strlen($line), RuntimeException::class, 'The message log archive could not be written.');
    }
}
