<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use BackedEnum;
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

    private function collectionName(BackedEnum|string $collectionName): string
    {
        return $collectionName instanceof BackedEnum ? (string) $collectionName->value : $collectionName;
    }
}
