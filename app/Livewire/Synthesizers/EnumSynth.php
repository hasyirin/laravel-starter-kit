<?php

declare(strict_types=1);

namespace App\Livewire\Synthesizers;

use Closure;
use Livewire\Mechanisms\HandleComponents\Synthesizers\EnumSynth as LivewireEnumSynth;
use TypeError;
use ValueError;

/**
 * Livewire gives 500 for an unknown enum value from the browser, and logs a value of the wrong type.
 * This synth gives 419 for both without a log entry, as Livewire does for a wrong type on assignment.
 */
class EnumSynth extends LivewireEnumSynth
{
    public static function hydrateFromType(mixed $type, mixed $value): mixed
    {
        return self::expireOnUnknownValue(fn (): mixed => parent::hydrateFromType($type, $value));
    }

    public function hydrate(mixed $value, mixed $meta): mixed
    {
        return self::expireOnUnknownValue(fn (): mixed => parent::hydrate($value, $meta));
    }

    /**
     * @param  Closure(): mixed  $hydrate
     */
    private static function expireOnUnknownValue(Closure $hydrate): mixed
    {
        try {
            return $hydrate();
        } catch (ValueError|TypeError $error) {
            throw_if(config('app.debug'), $error);

            abort(419);
        }
    }
}
