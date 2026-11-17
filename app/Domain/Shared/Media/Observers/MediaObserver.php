<?php

declare(strict_types=1);

namespace App\Domain\Shared\Media\Observers;

use Override;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver as SpatieMediaObserver;

/**
 * Removes the files of a new media on a rollback, and the files of a deleted media after the commit. See kit ADR-0023.
 */
class MediaObserver extends SpatieMediaObserver
{
    public function created(Media $media): void
    {
        $media->getConnection()->afterRollBack(fn () => rescue(fn () => resolve(Filesystem::class)->removeAllFiles($media)));
    }

    #[Override]
    public function deleted(Media $media): void
    {
        if (method_exists($media, 'isForceDeleting') && ! $media->isForceDeleting()) {
            return;
        }

        $media->getConnection()->afterCommit(fn () => rescue(fn () => resolve(Filesystem::class)->removeAllFiles($media)));
    }
}
