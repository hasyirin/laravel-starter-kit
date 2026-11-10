<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Enums\FieldType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class LetterData
{
    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $images
     */
    private function __construct(private array $fields, private array $values, private array $images) {}

    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $values
     * @param  list<Model>  $mediaOwners
     */
    public static function from(array $fields, array $values, array $mediaOwners = []): self
    {
        $read = [];
        $images = [];

        foreach ($fields as $field) {
            $value = $values[$field->name] ?? null;
            $read[$field->name] = self::read($field, $value, $mediaOwners);

            if ($field->type === FieldType::Image) {
                $images[$field->name] = $value === '' ? null : $value;
            }
        }

        return new self($fields, $read, $images);
    }

    public function __get(string $name): mixed
    {
        throw_unless(array_key_exists($name, $this->values), InvalidArgumentException::class, "The letter data has no field named [{$name}].");

        return $this->values[$name];
    }

    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return collect($this->fields)
            ->mapWithKeys(fn (Field $field): array => [$field->name => $this->stored($field)])
            ->all();
    }

    private function stored(Field $field): mixed
    {
        $read = $this->values[$field->name];

        if ($field->type === FieldType::Image) {
            throw_if($this->images[$field->name] instanceof TemporaryUploadedFile, LogicException::class, 'The upload must be stored as media before the letter data is stored.');

            return $this->images[$field->name];
        }

        return match (true) {
            $read instanceof CarbonInterface => $read->toDateString(),
            $read instanceof self => $read->toArray(),
            $field->type === FieldType::Table => collect((array) $read)->map(fn (mixed $row): mixed => $row instanceof self ? $row->toArray() : $row)->values()->all(),
            default => $read,
        };
    }

    /**
     * @param  list<Model>  $mediaOwners
     */
    private static function read(Field $field, mixed $value, array $mediaOwners): mixed
    {
        if ($value === null || $value === '') {
            return in_array($field->type, [FieldType::Table, FieldType::List], true) ? [] : null;
        }

        return match ($field->type) {
            FieldType::Text, FieldType::Lines, FieldType::Choice => (string) $value,
            FieldType::Date => $value instanceof CarbonInterface ? $value->toImmutable() : CarbonImmutable::parse((string) $value),
            FieldType::Number => is_numeric($value) ? $value + 0 : throw new InvalidArgumentException("The field [{$field->name}] is not a number."),
            FieldType::YesNo => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            FieldType::List => collect((array) $value)->map(fn (mixed $item): string => (string) $item)->values()->all(),
            FieldType::Table => collect((array) $value)->map(fn (mixed $row): self => self::from($field->children, self::keyed($row), $mediaOwners))->values()->all(),
            FieldType::Image => self::dataUri($value, $mediaOwners),
            FieldType::Group => self::group($field, self::keyed($value), $mediaOwners),
        };
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  list<Model>  $mediaOwners
     */
    private static function group(Field $field, array $value, array $mediaOwners): ?self
    {
        $group = self::from($field->children, $value, $mediaOwners);

        if (collect($group->values)->every(fn (mixed $item): bool => $item === null || $item === [])) {
            return null;
        }

        return $group;
    }

    /**
     * @return array<string, mixed>
     */
    private static function keyed(mixed $value): array
    {
        return collect((array) $value)->mapWithKeys(fn (mixed $item, int|string $key): array => [(string) $key => $item])->all();
    }

    /**
     * @param  list<Model>  $mediaOwners
     */
    private static function dataUri(mixed $source, array $mediaOwners): string
    {
        if ($source instanceof TemporaryUploadedFile) {
            throw_unless(in_array($source->getMimeType(), Field::RASTER_TYPES, true), InvalidArgumentException::class, 'The upload is not a raster image.');

            return self::encode($source->getMimeType(), $source->get());
        }

        $media = is_string($source) ? Field::ownedImage($source, $mediaOwners) : null;

        throw_if(! $media instanceof Media, InvalidArgumentException::class, 'The image is not a raster image of a media owner.');

        return self::encode($media->mime_type, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));
    }

    private static function encode(string $mimeType, string|false|null $contents): string
    {
        throw_if($contents === false || $contents === null, RuntimeException::class, 'The image file cannot be read.');

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
