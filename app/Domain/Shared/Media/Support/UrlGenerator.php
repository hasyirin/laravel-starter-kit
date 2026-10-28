<?php

declare(strict_types=1);

namespace App\Domain\Shared\Media\Support;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class UrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        return route('media.show', array_filter(['media' => $this->media, 'conversion' => $this->conversion?->getName()]));
    }
}
