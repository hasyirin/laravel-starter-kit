<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use Hasyirin\RunningNumber\Contracts\RunningNumber;
use Hasyirin\RunningNumber\Enums\UndoMode;
use Hasyirin\RunningNumber\Exceptions\RunningNumberAlreadyIssued;
use Hasyirin\RunningNumber\Exceptions\RunningNumberNotUndoable;
use Hasyirin\RunningNumber\Exceptions\RunningNumberTypeNotRegistered;
use Hasyirin\RunningNumber\Models\RunningNumber as RunningNumberRow;
use Hasyirin\RunningNumber\PendingRunningNumber;
use LogicException;

final readonly class ReferenceSequence
{
    private function __construct(private LetterType $type, private string $key) {}

    public static function for(LetterType $type, string $key): self
    {
        return new self($type, $key);
    }

    /**
     * @param  array<array-key, mixed>  $parts
     */
    public static function key(array $parts): string
    {
        return collect($parts)
            ->map(function (mixed $part): string {
                if (! is_string($part) && ! is_int($part)) {
                    throw new LogicException('Each part of a letter sequence key must be a string or an int, but ['.get_debug_type($part).'] was found. Check the organization of the signer or the printer.');
                }

                return (string) $part;
            })
            ->implode(':');
    }

    /**
     * @return array{number: int, cycle: int}
     */
    public function next(): array
    {
        $number = (int) $this->registered(fn (): string => $this->pending()->format('{number}')->padding(0)->generate());

        return ['number' => $number, 'cycle' => $this->cycle() ?? 1];
    }

    public function reserve(int $number): void
    {
        try {
            $this->registered(fn () => $this->pending()->skip($number));
        } catch (RunningNumberAlreadyIssued) {
        }
    }

    public function release(int $number, int $cycle): void
    {
        if ($this->cycle() !== $cycle) {
            return;
        }

        try {
            $this->pending()->undo($number);
        } catch (RunningNumberNotUndoable) {
        }
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $call
     * @return TResult
     */
    private function registered(callable $call): mixed
    {
        try {
            return $call();
        } catch (RunningNumberTypeNotRegistered) {
            $this->pending()->register(
                interval: $this->type->sequenceReset(),
                undoMode: UndoMode::RECORD,
                timezone: config()->string('system.timezone'),
            );

            return $call();
        }
    }

    private function cycle(): ?int
    {
        $cycle = RunningNumberRow::query()->type($this->key)->value('reset_cycle');

        return $cycle === null ? null : (int) $cycle;
    }

    private function pending(): PendingRunningNumber
    {
        return resolve(RunningNumber::class)->for($this->key);
    }
}
