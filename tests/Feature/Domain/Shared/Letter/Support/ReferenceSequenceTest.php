<?php

declare(strict_types=1);

use App\Domain\Shared\Letter\Support\ReferenceSequence;
use Hasyirin\RunningNumber\Enums\ResetInterval;
use Hasyirin\RunningNumber\Enums\UndoMode;
use Hasyirin\RunningNumber\Models\RunningNumber;
use Hasyirin\RunningNumber\Models\RunningNumberUndo;
use Tests\Fixtures\Support\NumberedNoticeLetter;
use Tests\Fixtures\Support\YearlyNoticeLetter;

beforeEach(function (): void {
    config(['system.letters.types' => [
        'numbered' => NumberedNoticeLetter::class,
        'yearly' => YearlyNoticeLetter::class,
    ]]);
});

function numberedSequence(string $key = 'numbered:1'): ReferenceSequence
{
    return ReferenceSequence::for(new NumberedNoticeLetter, $key);
}

test('the key joins its parts with a colon', function (): void {
    expect(ReferenceSequence::key(['numbered', 5]))->toBe('numbered:5');
});

test('a key part that is not a string or an int is refused', function (): void {
    expect(fn (): string => ReferenceSequence::key(['numbered', null]))->toThrow(LogicException::class);
});

test('the first number registers the sequence with its reset and recorded undos', function (): void {
    expect(ReferenceSequence::for(new YearlyNoticeLetter, 'yearly:1')->next())->toBe(['number' => 1, 'cycle' => 1]);

    $row = RunningNumber::query()->type('yearly:1')->sole();

    expect($row->reset_interval)->toBe(ResetInterval::YEARLY)
        ->and($row->undo_mode)->toBe(UndoMode::RECORD)
        ->and($row->timezone)->toBe(config('system.timezone'));
});

test('numbers count up within one key', function (): void {
    numberedSequence()->next();

    expect(numberedSequence()->next())->toBe(['number' => 2, 'cycle' => 1])
        ->and(numberedSequence('numbered:2')->next())->toBe(['number' => 1, 'cycle' => 1]);
});

test('a reserved number above the counter is never given', function (): void {
    numberedSequence()->reserve(2);

    expect(numberedSequence()->next()['number'])->toBe(1)
        ->and(numberedSequence()->next()['number'])->toBe(3);
});

test('a reserved number at or below the counter changes nothing', function (): void {
    numberedSequence()->next();
    numberedSequence()->next();

    numberedSequence()->reserve(1);

    expect(numberedSequence()->next()['number'])->toBe(3);
});

test('a released number in the current cycle is recorded and not given again', function (): void {
    numberedSequence()->next();

    numberedSequence()->release(1, 1);

    expect(RunningNumberUndo::query()->where('number', 1)->count())->toBe(1)
        ->and(numberedSequence()->next()['number'])->toBe(2);
});

test('releasing a number twice records one undo', function (): void {
    numberedSequence()->next();

    numberedSequence()->release(1, 1);
    numberedSequence()->release(1, 1);

    expect(RunningNumberUndo::query()->count())->toBe(1);
});

test('a released number of an ended cycle records nothing', function (): void {
    $this->travelTo('2026-06-01 10:00:00');
    $sequence = ReferenceSequence::for(new YearlyNoticeLetter, 'yearly:1');
    $sequence->next();

    $this->travelTo('2027-06-01 10:00:00');
    $sequence->next();
    $sequence->release(1, 1);

    expect(RunningNumberUndo::query()->count())->toBe(0);
});

test('a released number after the cycle ended but before the next number records nothing', function (): void {
    $this->travelTo('2026-06-01 10:00:00');
    $sequence = ReferenceSequence::for(new YearlyNoticeLetter, 'yearly:1');
    $sequence->next();

    $this->travelTo('2027-06-01 10:00:00');
    $sequence->release(1, 1);

    expect(RunningNumberUndo::query()->count())->toBe(0)
        ->and($sequence->next())->toBe(['number' => 1, 'cycle' => 2]);
});

test('releasing a number of an unknown sequence does nothing', function (): void {
    numberedSequence()->release(1, 1);

    expect(RunningNumber::query()->count())->toBe(0);
});
