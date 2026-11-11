<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Enums\FieldType;
use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class Field
{
    public const array RASTER_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    public const int MAX_IMAGE_KILOBYTES = 10240;

    /**
     * @param  array<string, string>  $options
     * @param  list<Field>  $children
     */
    private function __construct(
        public string $name,
        public FieldType $type,
        public ?string $label = null,
        public array $options = [],
        public array $children = [],
        public bool $isOptional = false,
    ) {}

    public static function text(string $name): self
    {
        return new self($name, FieldType::Text);
    }

    public static function lines(string $name): self
    {
        return new self($name, FieldType::Lines);
    }

    public static function date(string $name): self
    {
        return new self($name, FieldType::Date);
    }

    public static function number(string $name): self
    {
        return new self($name, FieldType::Number);
    }

    public static function yesNo(string $name): self
    {
        return new self($name, FieldType::YesNo);
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function choice(string $name, array $options): self
    {
        return new self($name, FieldType::Choice, options: $options);
    }

    public static function list(string $name): self
    {
        return new self($name, FieldType::List);
    }

    /**
     * @param  list<Field>  $columns
     */
    public static function table(string $name, array $columns): self
    {
        foreach ($columns as $column) {
            if ($column->type === FieldType::Image) {
                throw new LogicException("Table [{$name}] cannot have the image column [{$column->name}].");
            }
        }

        return new self($name, FieldType::Table, children: $columns);
    }

    public static function image(string $name): self
    {
        return new self($name, FieldType::Image);
    }

    /**
     * @param  list<Field>  $fields
     */
    public static function group(string $name, string $label, array $fields): self
    {
        return new self($name, FieldType::Group, $label, children: $fields);
    }

    public function optional(): static
    {
        return new self($this->name, $this->type, $this->label, $this->options, $this->children, true);
    }

    public function label(string $label): static
    {
        return new self($this->name, $this->type, $label, $this->options, $this->children, $this->isOptional);
    }

    /**
     * @param  list<Field>  $fields
     * @param  list<Model>  $mediaOwners
     * @return array<string, list<mixed>>
     */
    public static function rules(array $fields, array $mediaOwners = []): array
    {
        return collect($fields)
            ->flatMap(fn (Field $field): array => $field->ruleSet($field->name, $mediaOwners, null))
            ->all();
    }

    /**
     * @param  list<Model>  $mediaOwners
     * @param  array<int, string>|null  $filledWith
     * @return array<string, list<mixed>>
     */
    private function ruleSet(string $key, array $mediaOwners, ?array $filledWith): array
    {
        $presence = match (true) {
            $this->isOptional || $this->type === FieldType::Group || $filledWith === [] => ['nullable'],
            $filledWith !== null => ['nullable', 'required_with:'.implode(',', $filledWith)],
            default => ['required'],
        };

        return match ($this->type) {
            FieldType::Text => [$key => [...$presence, 'string', 'max:255']],
            FieldType::Lines => [$key => [...$presence, 'string', 'max:5000']],
            FieldType::Date => [$key => [...$presence, 'date']],
            FieldType::Number => [$key => [...$presence, 'numeric']],
            FieldType::YesNo => [$key => [...$presence, 'boolean']],
            FieldType::Choice => [$key => [...$presence, 'string', Rule::in(array_keys($this->options))]],
            FieldType::List => [$key => [...$presence, 'array'], $key.'.*' => ['required', 'string', 'max:255']],
            FieldType::Image => [$key => [...$presence, $this->imageRule($mediaOwners)]],
            FieldType::Table => $this->tableRules($key, $presence, $mediaOwners),
            FieldType::Group => $this->groupRules($key, $mediaOwners),
        };
    }

    /**
     * @param  list<string>  $presence
     * @param  list<Model>  $mediaOwners
     * @return array<string, list<mixed>>
     */
    private function tableRules(string $key, array $presence, array $mediaOwners): array
    {
        return collect($this->children)
            ->flatMap(fn (Field $column): array => $column->ruleSet($key.'.*.'.$column->name, $mediaOwners, null))
            ->prepend(['array'], $key.'.*')
            ->prepend([...$presence, 'array'], $key)
            ->all();
    }

    /**
     * @param  list<Model>  $mediaOwners
     * @return array<string, list<mixed>>
     */
    private function groupRules(string $key, array $mediaOwners): array
    {
        $leaves = collect($this->children)->mapWithKeys(fn (Field $child): array => [$child->name => $child->leafKeys($key.'.'.$child->name)]);

        return collect($this->children)
            ->flatMap(fn (Field $child): array => $child->ruleSet($key.'.'.$child->name, $mediaOwners, $leaves->except($child->name)->flatten()->map(fn (mixed $leaf): string => (string) $leaf)->values()->all()))
            ->prepend(['nullable', 'array'], $key)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function leafKeys(string $key): array
    {
        if ($this->type !== FieldType::Group) {
            return [$key];
        }

        return collect($this->children)->flatMap(fn (Field $child): array => $child->leafKeys($key.'.'.$child->name))->map(fn (mixed $leaf): string => (string) $leaf)->values()->all();
    }

    /**
     * @param  list<Model>  $mediaOwners
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private function imageRule(array $mediaOwners): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($mediaOwners): void {
            $valid = $value instanceof TemporaryUploadedFile
                ? in_array($value->getMimeType(), self::RASTER_TYPES, true)
                : is_string($value) && self::ownedImage($value, $mediaOwners) instanceof Media;

            if (! $valid) {
                $fail(__('validation.image', ['attribute' => $attribute]));

                return;
            }

            if ($value instanceof TemporaryUploadedFile && $value->getSize() > self::MAX_IMAGE_KILOBYTES * 1024) {
                $fail(__('validation.max.file', ['attribute' => $attribute, 'max' => self::MAX_IMAGE_KILOBYTES]));
            }
        };
    }

    /**
     * @param  list<Model>  $mediaOwners
     */
    public static function ownedImage(string $uuid, array $mediaOwners): ?Media
    {
        $owners = collect($mediaOwners)->filter(fn (Model $owner): bool => $owner->exists);

        if ($owners->isEmpty() || ! str($uuid)->isUuid()) {
            return null;
        }

        return Media::query()
            ->where('uuid', $uuid)
            ->whereIn('mime_type', self::RASTER_TYPES)
            ->where(fn (Builder $query) => $owners->each(fn (Model $owner) => $query->orWhere(
                fn (Builder $match) => $match->where('model_type', $owner->getMorphClass())->where('model_id', $owner->getKey()),
            )))
            ->first();
    }
}
