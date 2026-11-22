<?php

declare(strict_types=1);

namespace App\Livewire\Synthesizers;

use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateMalformedStringException;
use DateTimeInterface;
use Livewire\Mechanisms\HandleComponents\Synthesizers\CarbonSynth as LivewireCarbonSynth;
use TypeError;

/**
 * Livewire gives 500 for a date from the browser that it cannot parse, and logs the error.
 * It also keeps a year above 9999, which it writes in a form that it cannot parse on the next request.
 * This synth gives 419 for both without a log entry, as EnumSynth does for an unknown enum value.
 */
class CarbonSynth extends LivewireCarbonSynth
{
    public static function hydrateFromType(mixed $type, mixed $value): mixed
    {
        return self::expireOnMalformedDate(fn (): mixed => parent::hydrateFromType($type, $value));
    }

    public function hydrate(mixed $value, mixed $meta): mixed
    {
        return self::expireOnMalformedDate(fn (): mixed => parent::hydrate($value, $meta));
    }

    /**
     * @param  Closure(): mixed  $hydrate
     */
    private static function expireOnMalformedDate(Closure $hydrate): mixed
    {
        try {
            $date = $hydrate();

            if ($date instanceof DateTimeInterface) {
                new ($date::class)($date->format(DateTimeInterface::ATOM));
            }

            return $date;
        } catch (InvalidFormatException|DateMalformedStringException|TypeError $error) {
            throw_if(config('app.debug'), $error);

            abort(419);
        }
    }
}
