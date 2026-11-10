<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Support\CarbonCopy;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HandlesLetterFields
{
    /**
     * @return list<Model>
     */
    abstract protected function mediaOwners(): array;

    protected function printer(): User
    {
        /** @var User $printer */
        $printer = auth()->user();

        return $printer;
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<array-key, mixed>  $values
     * @return array<string, mixed>
     */
    protected function formValues(array $fields, array $values): array
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
    protected function imageItems(mixed $uuid): array
    {
        $media = is_string($uuid) ? Field::ownedImage($uuid, $this->mediaOwners()) : null;

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
    protected function payload(array $fields, array $values, string $path = ''): array
    {
        $groups = is_array($values['_groups'] ?? null) ? $values['_groups'] : [];

        return collect($fields)
            ->mapWithKeys(fn (Field $field): array => [
                $field->name => $this->payloadValue($field, $values[$field->name] ?? null, (bool) ($groups[$field->name] ?? false), $path.$field->name),
            ])
            ->all();
    }

    protected function payloadValue(Field $field, mixed $value, bool $groupOn, string $path): mixed
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
    protected function groupPayload(Field $field, mixed $value, bool $groupOn, string $path): ?array
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

    protected function imagePayload(mixed $items, string $path): TemporaryUploadedFile|string|null
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

    /**
     * @return array<string, list<mixed>>
     */
    protected function copyRules(mixed $kind, string $path): array
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

    /**
     * @return array<int, string>
     */
    protected function signerOptions(?LetterType $type): array
    {
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

    /**
     * @param  list<CarbonCopy>  $copies
     * @return list<array{kind: string, target: int|string|null, text: string}>
     */
    protected function copyRows(array $copies): array
    {
        $rows = collect($copies)
            ->map(fn (CarbonCopy $copy): array => match (true) {
                $copy->type === 'organization' && $copy->id !== null => ['kind' => 'organization', 'target' => $copy->id, 'text' => ''],
                $copy->type === 'department' && $copy->id !== null => ['kind' => 'department', 'target' => $copy->id, 'text' => ''],
                default => ['kind' => 'text', 'target' => null, 'text' => $copy->name],
            })
            ->all();

        return array_values($rows);
    }

    /**
     * @param  array<int, array{kind: string, target: int|string|null, text: string}>  $rows
     * @return list<CarbonCopy>
     */
    protected function copiesFromRows(array $rows): array
    {
        $copies = collect($rows)
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
     * @return array<string, list<mixed>>
     */
    protected function copyRowRules(bool $editable): array
    {
        $rules = collect($this->carbon_copies)
            ->flatMap(fn (mixed $row, int $index): array => $this->copyRules(data_get($row, 'kind'), "carbon_copies.$index"))
            ->all();

        return [
            'carbon_copies' => $editable ? ['array'] : [],
            'carbon_copies.*' => $editable ? ['array'] : [],
            'carbon_copies.*.kind' => $editable ? ['required', Rule::in(['organization', 'department', 'text'])] : [],
            ...($editable ? $rules : []),
        ];
    }
}
