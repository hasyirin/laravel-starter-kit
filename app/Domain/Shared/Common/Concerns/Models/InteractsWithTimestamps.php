<?php

declare(strict_types=1);

namespace App\Domain\Shared\Common\Concerns\Models;

use App\Domain\User\Support\Preferences;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait InteractsWithTimestamps
{
    /**
     * @return Attribute<string|null, never>
     */
    protected function formattedCreatedAt(): Attribute
    {
        return Attribute::make(get: fn (): ?string => Preferences::current()->datetime($this->created_at));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function formattedUpdatedAt(): Attribute
    {
        return Attribute::make(get: fn (): ?string => Preferences::current()->datetime($this->updated_at));
    }

    /**
     * A date-only value keeps the day it was given. Without this, `today('Asia/Kuala_Lumpur')` would move into
     * the app zone below and be saved as the day before.
     *
     * @param  string  $key
     */
    public function setAttribute($key, $value)
    {
        if ($value instanceof DateTimeInterface && preg_match('/^(immutable_)?date(:|$)/', $this->getCasts()[$key] ?? '') === 1) {
            $value = $value->format('Y-m-d');
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * PostgreSQL returns timestamptz values with a fixed offset, such as +01:00, and Eloquent keeps it.
     * The named zone of the app keeps date math right across daylight saving, and it puts a value
     * from another zone into the app zone before Eloquent writes it.
     */
    protected function asDateTime(mixed $value): CarbonInterface
    {
        return parent::asDateTime($value)->setTimezone(config()->string('app.timezone'));
    }
}
