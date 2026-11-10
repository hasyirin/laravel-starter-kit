<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Enums\FieldType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class LetterData
{
    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $stored
     */
    private function __construct(private array $values, private array $stored) {}

    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $values
     */
    public static function from(array $fields, array $values): self
    {
        $read = [];
        $stored = [];

        foreach ($fields as $field) {
            $value = $values[$field->name] ?? null;
            $read[$field->name] = self::read($field, $value);
            $stored[$field->name] = self::store($field, $value, $read[$field->name]);
        }

        return new self($read, $stored);
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
        return $this->stored;
    }

    private static function read(Field $field, mixed $value): mixed
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
            FieldType::Table => collect((array) $value)->map(fn (mixed $row): self => self::from($field->children, self::keyed($row)))->values()->all(),
            FieldType::Image => self::dataUri($value),
            FieldType::Group => self::group($field, self::keyed($value)),
        };
    }

    private static function store(Field $field, mixed $value, mixed $read): mixed
    {
        return match (true) {
            $read instanceof CarbonInterface => $read->toDateString(),
            $read instanceof self => $read->toArray(),
            $field->type === FieldType::Table => collect((array) $value)->map(fn (mixed $row): array => self::from($field->children, self::keyed($row))->toArray())->values()->all(),
            $field->type === FieldType::Image => $value,
            default => $read,
        };
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private static function group(Field $field, array $value): ?self
    {
        $group = self::from($field->children, $value);

        if (collect($group->toArray())->every(fn (mixed $item): bool => $item === null || $item === [])) {
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

    private static function dataUri(mixed $source): string
    {
        if ($source instanceof TemporaryUploadedFile) {
            return self::encode($source->getMimeType(), $source->get());
        }

        $media = Media::query()->where('uuid', (string) $source)->firstOrFail();

        return self::encode($media->mime_type, Storage::disk($media->disk)->get($media->getPathRelativeToRoot()));
    }

    private static function encode(string $mimeType, string|false|null $contents): string
    {
        throw_if($contents === false || $contents === null, RuntimeException::class, 'The image file cannot be read.');

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
