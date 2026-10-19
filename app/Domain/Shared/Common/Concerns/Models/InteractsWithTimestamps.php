<?php

namespace App\Domain\Shared\Common\Concerns\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait InteractsWithTimestamps
{
    /**
     * @return Attribute<string|null, never>
     */
    protected function formattedCreatedAt(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->created_at?->format(config('system.formats.datetime')));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function formattedUpdatedAt(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->updated_at?->format(config('system.formats.datetime')));
    }
}
