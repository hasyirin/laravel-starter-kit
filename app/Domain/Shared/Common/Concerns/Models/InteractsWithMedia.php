<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use BackedEnum;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia as InteractsWithSpatieMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithMedia
{
    use InteractsWithSpatieMedia {
        InteractsWithSpatieMedia::hasMedia as private hasMediaNamed;
        InteractsWithSpatieMedia::getMedia as private getMediaNamed;
        InteractsWithSpatieMedia::getFirstMedia as private getFirstMediaNamed;
        InteractsWithSpatieMedia::getFirstMediaUrl as private getFirstMediaUrlNamed;
        InteractsWithSpatieMedia::clearMediaCollection as private clearMediaCollectionNamed;
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // ImageMagick can fetch the files that an SVG links to while it draws the SVG, so an SVG gets no preview.
        if ($media?->mime_type === 'image/svg+xml') {
            return;
        }

        $this->addMediaConversion('preview')
            ->fit(Fit::Max, 300, 300)
            ->format('webp');
    }

    /**
     * @param  array<string, mixed>|callable(Media): bool  $filters
     */
    public function hasMedia(BackedEnum|string $collectionName = 'default', array|callable $filters = []): bool
    {
        return $this->hasMediaNamed($this->collectionName($collectionName), $filters);
    }

    /**
     * @param  array<string, mixed>|callable(Media): bool  $filters
     * @return MediaCollection<int, Media>
     */
    public function getMedia(BackedEnum|string $collectionName = 'default', array|callable $filters = []): MediaCollection
    {
        return $this->getMediaNamed($this->collectionName($collectionName), $filters);
    }

    /**
     * @param  array<string, mixed>|callable(Media): bool  $filters
     */
    public function getFirstMedia(BackedEnum|string $collectionName = 'default', array|callable $filters = []): ?Media
    {
        return $this->getFirstMediaNamed($this->collectionName($collectionName), $filters);
    }

    public function getFirstMediaUrl(BackedEnum|string $collectionName = 'default', string $conversionName = ''): string
    {
        return $this->getFirstMediaUrlNamed($this->collectionName($collectionName), $conversionName);
    }

    public function clearMediaCollection(BackedEnum|string $collectionName = 'default'): static
    {
        $this->clearMediaCollectionNamed($this->collectionName($collectionName));

        return $this;
    }

    public function addMediaFromUpload(TemporaryUploadedFile $upload, BackedEnum|string $collectionName = 'default'): Media
    {
        throw_unless($this->exists, LogicException::class, 'Save the model before you add an upload to its media.');

        $path = tempnam(sys_get_temp_dir(), 'media-library');

        throw_if($path === false, RuntimeException::class, 'The temp folder cannot take a copy of the upload.');

        try {
            $stream = $upload->readStream();

            throw_unless(is_resource($stream), RuntimeException::class, 'The upload cannot be read, so it cannot become media.');

            file_put_contents($path, $stream);

            return $this->addMedia($path)
                ->usingName(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME))
                ->usingFileName($upload->getClientOriginalName())
                ->toMediaCollection($this->collectionName($collectionName));
        } finally {
            File::delete($path);
        }
    }

    private function collectionName(BackedEnum|string $collectionName): string
    {
        return $collectionName instanceof BackedEnum ? (string) $collectionName->value : $collectionName;
    }
}
