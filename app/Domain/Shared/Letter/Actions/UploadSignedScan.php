<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Media\Actions\SyncMedia;
use App\Domain\Shared\Media\Enums\MediaCollection;

class UploadSignedScan
{
    use Actionable;

    /**
     * @param  list<array{uuid: string, name: string, size: int, mime_type: string, preview?: bool, upload?: ?string}>  $items
     */
    public function execute(Letter $letter, array $items): Letter
    {
        SyncMedia::make()->execute($letter, $items, MediaCollection::SignedScan);

        return $letter;
    }
}
