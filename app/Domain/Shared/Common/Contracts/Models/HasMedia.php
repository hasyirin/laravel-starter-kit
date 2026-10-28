<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Contracts\Models;

use BackedEnum;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface HasMedia extends \Spatie\MediaLibrary\HasMedia
{
    /**
     * @param  array<string, mixed>|callable(Media): bool  $filters
     */
    public function hasMedia(BackedEnum|string $collectionName = 'default', array|callable $filters = []): bool;

    /**
     * @param  array<string, mixed>|callable(Media): bool  $filters
     * @return MediaCollection<int, Media>
     */
    public function getMedia(BackedEnum|string $collectionName = 'default', array|callable $filters = []): MediaCollection;
}
