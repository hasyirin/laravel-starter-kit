<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Shared;

use App\Domain\Shared\Letter\Actions\SaveLetter;
use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Enums\NumberingMode;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Livewire\Form;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LetterForm extends Form
{
    private const array PARTS = ['type', 'reference_number', 'sequence_number', 'date', 'signer', 'carbon_copies', 'with_letterhead', 'computer_signed', 'extra'];

    private const array PRINT_PARTS = ['signer', 'carbon_copies', 'with_letterhead', 'computer_signed'];

    #[Locked]
    public Model $source;

    #[Locked]
    public Letter $letter;

    public string $type = '';

    public string $reference_number = '';

    public ?int $sequence_number = null;

    public ?CarbonImmutable $date = null;

    public ?int $signer_id = null;

    /** @var list<array{kind: string, target: int|string|null, text: string}> */
    public array $carbon_copies = [];

    public bool $with_letterhead = false;

    public bool $computer_signed = false;

    /** @var array<string, mixed> */
    public array $extra = [];

    public function set(Model $source, Letter $letter): static
    {
        $this->source = $source;
        $this->letter = $letter;
        $this->type = $letter->exists ? $letter->type : $this->onlyType();
        $this->reference_number = '';
        $this->sequence_number = null;
        $this->date = null;
        $this->signer_id = null;
        $this->carbon_copies = [];
        $this->with_letterhead = false;
        $this->computer_signed = false;
        $this->extra = $this->savedExtra();

        foreach (['reference_number', 'sequence_number', 'date', 'signer', 'carbon_copies', 'with_letterhead', 'computer_signed'] as $part) {
            $this->keepSaved($part);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $copyRules = collect($this->carbon_copies)
            ->flatMap(fn (mixed $row, int $index): array => $this->copyRules(data_get($row, 'kind'), "carbon_copies.$index"))
            ->all();

        $signerRules = $this->editable('signer') ? ['nullable', 'integer', Rule::in(array_keys($this->signers()))] : [];
        $copiesEditable = $this->editable('carbon_copies');

        return [
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'sequence_number' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date'],
            'signer_id' => $signerRules,
            'carbon_copies' => $copiesEditable ? ['array'] : [],
            'carbon_copies.*' => $copiesEditable ? ['array'] : [],
            'carbon_copies.*.kind' => $copiesEditable ? ['required', Rule::in(['organization', 'department', 'text'])] : [],
            ...($copiesEditable ? $copyRules : []),
            'with_letterhead' => ['boolean'],
            'computer_signed' => ['boolean'],
        ];
    }

    public function letterType(): ?LetterType
    {
        return LetterTypes::all()[$this->type] ?? null;
    }

    /**
     * @return array<string, LetterType>
     */
    public function types(): array
    {
        $printer = $this->printer();
        $new = ! $this->letter->exists;

        return collect(LetterTypes::forSource($this->source))
            ->filter(fn (LetterType $type): bool => $type->allowedFor($printer) && (! $new || $this->creatable($type)))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function signers(): array
    {
        $type = $this->letterType();
        $printer = $this->printer();
        $organizationId = $printer->profile?->organization_id;

        if (! $type instanceof LetterType || $type->signer() === SignerRequirement::Hidden || $organizationId === null) {
            return [];
        }

        $users = User::query()
            ->whereHas('profile', fn (Builder $profile): Builder => $profile->whereNotNull('position_id')->where('organization_id', $organizationId))
            ->orderBy('name');

        /** @var array<int, string> $signers */
        $signers = $type->signers($users, $printer)->pluck('name', 'id')->all();

        return $signers;
    }

    public function chooseType(): void
    {
        $this->extra = $this->formValues($this->letterType()?->extraFields() ?? [], []);
        $this->signer_id = null;
        $this->with_letterhead = false;
        $this->computer_signed = false;
    }

    public function editable(string $part): bool
    {
        $type = $this->letterType();

        if ($part === 'type') {
            return ! $this->letter->exists && ! in_array('type', $type?->lockedFields() ?? [], true);
        }

        if (! $type instanceof LetterType || in_array($part, $type->lockedFields(), true) || ! $this->offered($type, $part)) {
            return false;
        }

        if (! $this->letter->exists) {
            return ! in_array($part, self::PRINT_PARTS, true) || $this->can($type->sourceAbility('print'), $this->source);
        }

        return $this->can(in_array($part, self::PRINT_PARTS, true) ? 'print' : 'update', $this->letter);
    }

    public function save(): Letter
    {
        foreach (self::PARTS as $part) {
            if (! $this->editable($part)) {
                $this->keepSaved($part);
            }
        }

        $this->keepLockedExtra();

        $this->validate();

        $type = $this->letterType();
        assert($type instanceof LetterType);

        if (! $this->letter->exists) {
            $this->authorize('create', $this->source->morphMany(Letter::class, 'source')->make(['type' => $this->type]));
        }

        try {
            return SaveLetter::make()->execute(
                $this->letter,
                $type,
                $this->source,
                $this->printer(),
                $this->details($type),
                $this->payload($type->extraFields(), $this->extra),
            );
        } catch (ValidationException $validationException) {
            throw $this->withFormKeys($validationException);
        }
    }

    private function printer(): User
    {
        /** @var User $printer */
        $printer = auth()->user();

        return $printer;
    }

    private function onlyType(): string
    {
        $types = array_keys($this->types());

        return count($types) === 1 ? $types[0] : '';
    }

    private function offered(LetterType $type, string $part): bool
    {
        return match ($part) {
            'reference_number' => $type->numbering() === NumberingMode::Manual,
            'sequence_number' => $type->numbering() === NumberingMode::AutomaticWithOverride,
            'signer' => $type->signer() !== SignerRequirement::Hidden,
            'with_letterhead' => $type->offersLetterhead(),
            'computer_signed' => $type->offersComputerSigned(),
            'extra' => $type->extraFields() !== [],
            default => true,
        };
    }

    private function keepSaved(string $part): void
    {
        match ($part) {
            'type' => $this->type = $this->letter->exists ? $this->letter->type : '',
            'reference_number' => $this->reference_number = $this->letter->reference_number ?? '',
            'sequence_number' => $this->sequence_number = $this->letter->sequence_number,
            'date' => $this->date = $this->letter->exists ? $this->letter->date : CarbonImmutable::today(),
            'signer' => $this->signer_id = $this->letter->exists ? $this->letter->signature?->actor_id : null,
            'carbon_copies' => $this->carbon_copies = $this->savedCopies(),
            'with_letterhead' => $this->with_letterhead = $this->letter->with_letterhead,
            'computer_signed' => $this->computer_signed = $this->letter->computer_signed,
            default => $this->extra = $this->savedExtra(),
        };
    }

    private function keepLockedExtra(): void
    {
        $saved = $this->savedExtra();
        $savedGroups = is_array($saved['_groups'] ?? null) ? $saved['_groups'] : [];
        $groups = is_array($this->extra['_groups'] ?? null) ? $this->extra['_groups'] : [];

        foreach ($this->letterType()?->lockedFields() ?? [] as $name) {
            if (array_key_exists($name, $saved)) {
                $this->extra[$name] = $saved[$name];
            }

            if (array_key_exists($name, $savedGroups)) {
                $groups[$name] = $savedGroups[$name];
            }
        }

        if ($groups !== []) {
            $this->extra['_groups'] = $groups;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function savedExtra(): array
    {
        $values = $this->letter->exists ? $this->letter->extra : [];

        return $this->formValues($this->letterType()?->extraFields() ?? [], $values);
    }

    /**
     * @return list<array{kind: string, target: int|string|null, text: string}>
     */
    private function savedCopies(): array
    {
        $rows = collect($this->letter->exists ? $this->letter->carbon_copies : [])
            ->map(fn (CarbonCopy $copy): array => match (true) {
                $copy->type === 'organization' && $copy->id !== null => ['kind' => 'organization', 'target' => $copy->id, 'text' => ''],
                $copy->type === 'department' && $copy->id !== null => ['kind' => 'department', 'target' => $copy->id, 'text' => ''],
                default => ['kind' => 'text', 'target' => null, 'text' => $copy->name],
            })
            ->all();

        return array_values($rows);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function copyRules(mixed $kind, string $path): array
    {
        $departments = Rule::exists('departments', 'id')
            ->where('organization_id', $this->printer()->profile?->organization_id)
            ->withoutTrashed();

        return match ($kind) {
            'organization' => ["$path.target" => ['required', Rule::exists('organizations', 'id')->withoutTrashed()]],
            'department' => ["$path.target" => ['required', $departments]],
            default => ["$path.text" => ['required', 'string', 'max:255']],
        };
    }

    private function creatable(LetterType $type): bool
    {
        $lockedRequired = collect($type->extraFields())->contains(
            fn (Field $field): bool => in_array($field->name, $type->lockedFields(), true) && ! $field->isOptional && $field->type !== FieldType::Group,
        );

        return ! $lockedRequired && $this->can($type->sourceAbility('create'), $this->source);
    }

    private function signer(): ?User
    {
        if (! $this->editable('signer')) {
            $actor = $this->letter->exists ? $this->letter->signature?->actor : null;

            return $actor instanceof User ? $actor : null;
        }

        return $this->signer_id === null ? null : User::query()->findOrFail($this->signer_id);
    }

    private function details(LetterType $type): PrintDetails
    {
        $signer = $this->signer();

        return new PrintDetails(
            $type->numbering() === NumberingMode::Manual && $this->reference_number !== '' ? $this->reference_number : null,
            $type->numbering() === NumberingMode::AutomaticWithOverride ? $this->sequence_number : null,
            $this->date,
            $signer,
            $this->copies(),
            $this->with_letterhead,
            $this->computer_signed,
        );
    }

    /**
     * @return list<CarbonCopy>
     */
    private function copies(): array
    {
        if (! $this->editable('carbon_copies')) {
            return $this->letter->exists ? $this->letter->carbon_copies : [];
        }

        $copies = collect($this->carbon_copies)
            ->map(function (array $row): CarbonCopy {
                if ($row['kind'] === 'organization') {
                    $organization = Organization::query()->findOrFail((int) $row['target']);

                    return new CarbonCopy($organization->name, 'organization', $organization->id);
                }

                if ($row['kind'] === 'department') {
                    $department = Department::query()->findOrFail((int) $row['target']);

                    return new CarbonCopy($department->name, 'department', $department->id);
                }

                return new CarbonCopy($row['text']);
            })
            ->all();

        return array_values($copies);
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<array-key, mixed>  $values
     * @return array<string, mixed>
     */
    private function formValues(array $fields, array $values): array
    {
        $form = [];
        $groups = [];

        foreach ($fields as $field) {
            $value = $values[$field->name] ?? null;

            if ($field->type === FieldType::Group) {
                $groups[$field->name] = is_array($value);
                $form[$field->name] = $this->formValues($field->children, is_array($value) ? $value : []);

                continue;
            }

            $form[$field->name] = $field->type === FieldType::Image ? $this->imageItems($value) : $value;
        }

        return $groups === [] ? $form : [...$form, '_groups' => $groups];
    }

    /**
     * @return list<array{uuid: string, name: string, size: int, mime_type: string, preview: bool}>
     */
    private function imageItems(mixed $uuid): array
    {
        $media = is_string($uuid) ? Field::ownedImage($uuid, [$this->letter]) : null;

        if (! $media instanceof Media) {
            return [];
        }

        return [[
            'uuid' => $media->uuid,
            'name' => $media->file_name,
            'size' => $media->size,
            'mime_type' => $media->mime_type,
            'preview' => $media->hasGeneratedConversion('preview'),
        ]];
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<array-key, mixed>  $values
     * @return array<string, mixed>
     */
    private function payload(array $fields, array $values, string $path = ''): array
    {
        $groups = is_array($values['_groups'] ?? null) ? $values['_groups'] : [];

        return collect($fields)
            ->mapWithKeys(fn (Field $field): array => [
                $field->name => $this->payloadValue($field, $values[$field->name] ?? null, (bool) ($groups[$field->name] ?? false), $path.$field->name),
            ])
            ->all();
    }

    private function payloadValue(Field $field, mixed $value, bool $groupOn, string $path): mixed
    {
        return match ($field->type) {
            FieldType::Group => $this->groupPayload($field, $value, $groupOn, $path),
            FieldType::Image => $this->imagePayload($value, $path),
            FieldType::Table => is_array($value)
                ? collect($value)->map(fn (mixed $row, int|string $index): mixed => is_array($row) ? $this->payload($field->children, $row, "$path.$index.") : $row)->values()->all()
                : null,
            default => $value === '' ? null : $value,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function groupPayload(Field $field, mixed $value, bool $groupOn, string $path): ?array
    {
        if (! $groupOn) {
            return null;
        }

        $children = $this->payload($field->children, is_array($value) ? $value : [], $path.'.');
        $filled = collect($children)->contains(fn (mixed $child): bool => $child !== null && $child !== []);

        if (! $filled) {
            throw ValidationException::withMessages([$path => __('validation.required', ['attribute' => $field->label ?? $field->name])]);
        }

        return $children;
    }

    private function imagePayload(mixed $items, string $path): TemporaryUploadedFile|string|null
    {
        $item = is_array($items) ? collect($items)->first() : null;

        if (! is_array($item)) {
            return null;
        }

        if (! isset($item['upload'])) {
            return is_string($item['uuid'] ?? null) ? $item['uuid'] : null;
        }

        $file = is_string($item['upload']) ? TemporaryUploadedFile::unserializeFromLivewireRequest($item['upload']) : null;

        if (! $file instanceof TemporaryUploadedFile || ! $file->exists()) {
            throw ValidationException::withMessages([$path => __('common/uploads.expired', ['name' => is_string($item['name'] ?? null) ? $item['name'] : ''])]);
        }

        return $file;
    }

    private function withFormKeys(ValidationException $exception): ValidationException
    {
        $messages = collect($exception->errors())->mapWithKeys(fn (mixed $errors, int|string $key): array => [
            match ($key) {
                'signer' => 'form.signer_id',
                'reference_number', 'sequence_number' => "form.$key",
                default => "form.extra.$key",
            } => $errors,
        ]);

        return ValidationException::withMessages($messages->all());
    }
}
