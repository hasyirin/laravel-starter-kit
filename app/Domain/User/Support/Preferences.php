<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;

final readonly class Preferences
{
    public function __construct(
        public string $timezone,
        public string $locale,
    ) {}

    public static function current(): self
    {
        $timezone = context()->getHidden('timezone');
        $locale = context()->getHidden('locale');

        return new self(
            is_string($timezone) ? $timezone : config()->string('system.timezone'),
            is_string($locale) ? $locale : config()->string('system.locale'),
        );
    }

    public static function of(User $user): self
    {
        return new self(
            $user->timezone ?? config()->string('system.timezone'),
            $user->locale ?? config()->string('system.locale'),
        );
    }

    public function local(DateTimeInterface $value): CarbonImmutable
    {
        return CarbonImmutable::instance($value)->setTimezone($this->timezone);
    }

    public function date(?DateTimeInterface $value): ?string
    {
        return $value instanceof DateTimeInterface ? $this->format($this->local($value), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE) : null;
    }

    /**
     * A date-only value, such as a `date` cast, has no time zone, so it keeps its own: a date at midnight stays on its day in every zone.
     */
    public function calendarDate(?DateTimeInterface $value): ?string
    {
        return $value instanceof DateTimeInterface ? $this->format($value, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE) : null;
    }

    public function datetime(?DateTimeInterface $value): ?string
    {
        return $value instanceof DateTimeInterface ? $this->format($this->local($value), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT) : null;
    }

    public function time(?DateTimeInterface $value): ?string
    {
        return $value instanceof DateTimeInterface ? $this->format($this->local($value), IntlDateFormatter::NONE, IntlDateFormatter::SHORT) : null;
    }

    /**
     * ICU has its own time zone data, which can be older than PHP's: it may not know a new zone, or use an old offset.
     * So PHP moves the value to its zone, and ICU only gets the offset of that moment.
     */
    private function format(DateTimeInterface $value, int $date, int $time): string
    {
        return (string) new IntlDateFormatter($this->locale, $date, $time, new DateTimeZone($value->format('P')))->format($value);
    }
}
