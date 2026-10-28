<?php

declare(strict_types=1);

namespace App\Domain\Shared\Media\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SyncMedia
{
    use Actionable;

    /**
     * The browser sends the items, so only media of this collection is kept and only uploads that Livewire signed are added.
     *
     * @param  list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}>  $items
     */
    public function execute(Model&HasMedia $model, array $items, BackedEnum|string $collection = 'default'): void
    {
        $existing = $model->getMedia($collection)->keyBy('uuid');

        $entries = collect($items)->map(function (array $item) use ($existing): Media|TemporaryUploadedFile|null {
            if (! isset($item['upload'])) {
                return $existing->get($item['uuid']);
            }

            $file = TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']);

            if (! $file instanceof TemporaryUploadedFile || ! $file->exists()) {
                throw new RuntimeException("The upload of [{$item['name']}] is missing. Validate the items with the Uploads rule first.");
            }

            return $file;
        })->filter();

        $ids = $entries->map(function (Media|TemporaryUploadedFile $entry) use ($model, $collection): int {
            if ($entry instanceof Media) {
                return $entry->id;
            }

            $media = $model->addMediaFromStream($entry->readStream())
                ->usingName(pathinfo($entry->getClientOriginalName(), PATHINFO_FILENAME))
                ->usingFileName($entry->getClientOriginalName())
                ->toMediaCollection($collection instanceof BackedEnum ? (string) $collection->value : $collection);

            $entry->delete();

            return $media->id;
        });

        $existing->whereNotIn('id', $ids)->each(function (Media $media): void {
            $media->delete();
        });

        Media::setNewOrder($ids->values()->all());

        $model->unsetRelation('media');
    }
}
