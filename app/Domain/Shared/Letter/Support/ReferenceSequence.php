<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use Hasyirin\RunningNumber\Contracts\RunningNumber;
use Hasyirin\RunningNumber\Enums\UndoMode;
use Hasyirin\RunningNumber\Exceptions\RunningNumberAlreadyIssued;
use Hasyirin\RunningNumber\Exceptions\RunningNumberNotUndoable;
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
        $this->register();

        $number = (int) $this->pending()->format('{number}')->padding(0)->generate();

        return ['number' => $number, 'cycle' => (int) $this->row()?->reset_cycle];
    }

    public function reserve(int $number): void
    {
        $this->register();

        try {
            $this->pending()->skip($number);
        } catch (RunningNumberAlreadyIssued) {
        }
    }

    public function release(int $number, int $cycle): void
    {
        if ($this->row()?->reset_cycle !== $cycle) {
            return;
        }

        try {
            $this->pending()->undo($number);
        } catch (RunningNumberNotUndoable) {
        }
    }

    private function register(): void
    {
        $row = $this->row();
        $interval = $this->type->sequenceReset();
        $timezone = config()->string('system.timezone');

        if ($row?->undo_mode === UndoMode::RECORD && $row->reset_interval === $interval && $row->timezone === $timezone) {
            return;
        }

        $this->pending()->register(interval: $interval, undoMode: UndoMode::RECORD, timezone: $timezone);
    }

    private function row(): ?RunningNumberRow
    {
        return RunningNumberRow::query()->type($this->type())->first();
    }

    private function pending(): PendingRunningNumber
    {
        return resolve(RunningNumber::class)->for($this->type());
    }

    private function type(): string
    {
        return "letter:{$this->key}";
    }
}
