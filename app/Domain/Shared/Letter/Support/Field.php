<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Support;

use App\Domain\Shared\Letter\Enums\FieldType;
use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class Field
{
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
     * @return array<string, list<mixed>>
     */
    private function ruleSet(string $key, array $mediaOwners, ?string $group): array
    {
        $presence = match (true) {
            $this->isOptional || $this->type === FieldType::Group => ['nullable'],
            $group !== null => ['required_with:'.$group],
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
        return collect($this->children)
            ->flatMap(fn (Field $child): array => $child->ruleSet($key.'.'.$child->name, $mediaOwners, $key))
            ->prepend(['nullable', 'array'], $key)
            ->all();
    }

    /**
     * @param  list<Model>  $mediaOwners
     * @return Closure(string, mixed, Closure(string): mixed): void
     */
    private function imageRule(array $mediaOwners): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($mediaOwners): void {
            if ($value instanceof TemporaryUploadedFile) {
                if (! str_starts_with($value->getMimeType(), 'image/')) {
                    $fail(__('validation.image', ['attribute' => $attribute]));
                }

                return;
            }

            if (! is_string($value) || ! $this->ownsImage($value, $mediaOwners)) {
                $fail(__('validation.image', ['attribute' => $attribute]));
            }
        };
    }

    /**
     * @param  list<Model>  $mediaOwners
     */
    private function ownsImage(string $uuid, array $mediaOwners): bool
    {
        $owners = collect($mediaOwners)->filter(fn (Model $owner): bool => $owner->exists);

        if ($owners->isEmpty() || ! str($uuid)->isUuid()) {
            return false;
        }

        return Media::query()
            ->where('uuid', $uuid)
            ->where('mime_type', 'like', 'image/%')
            ->where(fn (Builder $query) => $owners->each(fn (Model $owner) => $query->orWhere(
                fn (Builder $match) => $match->where('model_type', $owner->getMorphClass())->where('model_id', $owner->getKey()),
            )))
            ->exists();
    }
}
