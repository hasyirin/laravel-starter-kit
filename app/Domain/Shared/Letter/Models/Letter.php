<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Models;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Common\Concerns\Models\InteractsWithMedia;
use App\Domain\Shared\Common\Contracts\Models\HasMedia;
use App\Domain\Shared\Common\Models\Model;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Factories\LetterFactory;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use Carbon\CarbonImmutable;
use Hasyirin\Actor\Concerns\InteractsWithActions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $type
 * @property string $source_type
 * @property int $source_id
 * @property string|null $reference_number
 * @property string|null $sequence_key
 * @property int|null $sequence_number
 * @property int|null $sequence_cycle
 * @property CarbonImmutable|null $date
 * @property list<CarbonCopy> $carbon_copies
 * @property array<string, mixed> $extra
 * @property bool $with_letterhead
 * @property bool $computer_signed
 * @property-read LetterType $letter_type
 * @property-read \Illuminate\Database\Eloquent\Model|null $source
 */
class Letter extends Model implements HasMedia
{
    /** @use HasFactory<LetterFactory> */
    use HasFactory;

    use InteractsWithActions;
    use InteractsWithMedia;

    protected $fillable = [
        'type',
        'reference_number',
        'sequence_key',
        'sequence_number',
        'sequence_cycle',
        'date',
        'carbon_copies',
        'extra',
        'with_letterhead',
        'computer_signed',
    ];

    protected $attributes = [
        'carbon_copies' => '[]',
        'extra' => '[]',
        'with_letterhead' => false,
        'computer_signed' => false,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'extra' => 'array',
            'with_letterhead' => 'boolean',
            'computer_signed' => 'boolean',
        ];
    }

    protected static function newFactory(): LetterFactory
    {
        return LetterFactory::new();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * @return MorphOne<Action, $this>
     */
    public function signature(): MorphOne
    {
        return $this->morphOne(config('actor.models.action'), 'resource')->ofName(Act::Sign);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(LetterMedia::Prints->value);
        $this->addMediaCollection(LetterMedia::SignedScan->value)->singleFile();
        $this->addMediaCollection(LetterMedia::Data->value);
    }

    /**
     * @param  array<array-key, mixed>  $copies
     */
    private function encodeCopies(array $copies): string
    {
        return json_encode(
            collect($copies)->map(fn (mixed $copy): array => $this->copyToArray($copy))->all(),
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array{name: string, type: string|null, id: int|null}
     */
    private function copyToArray(mixed $copy): array
    {
        if ($copy instanceof CarbonCopy) {
            return $copy->toArray();
        }

        if (is_array($copy) && is_string($copy['name'] ?? null)) {
            $type = $copy['type'] ?? null;
            $id = $copy['id'] ?? null;

            return new CarbonCopy($copy['name'], is_string($type) ? $type : null, is_int($id) ? $id : null)->toArray();
        }

        throw new InvalidArgumentException('A carbon copy must be a CarbonCopy or an array with a string name.');
    }

    /**
     * @return Attribute<LetterType, never>
     */
    protected function letterType(): Attribute
    {
        return Attribute::get(fn (): LetterType => LetterTypes::find($this->type));
    }

    /**
     * @return Attribute<list<CarbonCopy>, list<CarbonCopy>>
     */
    protected function carbonCopies(): Attribute
    {
        return Attribute::make(
            get: function (mixed $json): array {
                /** @var list<array{name: string, type?: string|null, id?: int|null}> $values */
                $values = json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);

                return collect($values)
                    ->map(fn (array $copy): CarbonCopy => CarbonCopy::fromArray($copy))
                    ->all();
            },
            set: fn (array $copies): string => $this->encodeCopies($copies),
        );
    }
}
