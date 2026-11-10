<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Enums\NumberingMode;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\User\Models\User;
use Carbon\CarbonImmutable;
use Hasyirin\RunningNumber\Enums\ResetInterval;
use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class LetterType
{
    abstract public function view(): string;

    /**
     * @return list<Field>
     */
    abstract public function fields(): array;

    /**
     * @return class-string<Model>
     */
    abstract public function source(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function data(Model $source, CarbonImmutable $date): array;

    public function locale(): string
    {
        return config()->string('system.locale');
    }

    public function dateFormat(): string
    {
        return 'd MMMM y';
    }

    public function signer(): SignerRequirement
    {
        return SignerRequirement::Optional;
    }

    public function allowedFor(User $printer): bool
    {
        return true;
    }

    public function keepsPrints(): bool
    {
        return false;
    }

    public function letterhead(): Letterhead|LetterheadView|null
    {
        return null;
    }

    public function offersLetterhead(): bool
    {
        return true;
    }

    public function offersComputerSigned(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function lockedFields(): array
    {
        return [];
    }

    /**
     * @return list<Field>
     */
    public function extraFields(): array
    {
        return [];
    }

    public function numbering(): NumberingMode
    {
        return NumberingMode::Manual;
    }

    /**
     * @return list<string|int|null>
     */
    public function sequenceKey(Letter $letter, User $printer): array
    {
        $signature = $letter->signature;

        if ($signature === null) {
            return [LetterTypes::keyOf($this), $printer->profile?->organization_id];
        }

        return [LetterTypes::keyOf($this), $signature->profile()->withTrashed()->first()?->organization_id];
    }

    public function sequenceReset(): ResetInterval
    {
        return ResetInterval::NEVER;
    }

    public function reference(int $number, Letter $letter): string
    {
        return (string) $number;
    }

    public function sourceAbility(string $letterAbility): string
    {
        return match ($letterAbility) {
            'create', 'update', 'delete' => 'update',
            default => 'view',
        };
    }

    final public function letterData(Model $source, CarbonImmutable $date): LetterData
    {
        $values = $this->data($source, $date);
        $key = LetterTypes::keyOf($this);
        $declared = collect($this->fields())->map(fn (Field $field): string => $field->name);

        $undeclared = collect($values)->keys()->diff($declared)->first();
        throw_if($undeclared !== null, LogicException::class, "{$key}: undeclared letter data [{$undeclared}]");

        $missing = $declared->diff(collect($values)->keys())->first();
        throw_if($missing !== null, LogicException::class, "{$key}: missing letter data [{$missing}]");

        validator($values, Field::rules($this->fields(), [$source]))->validate();

        return LetterData::from($this->fields(), $values, [$source]);
    }
}
