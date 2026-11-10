<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;

class SaveLetter
{
    use Actionable;

    /**
     * @param  array<string, mixed>  $extra
     */
    public function execute(Letter $letter, LetterType $type, Model $source, User $user, PrintDetails $details, array $extra): Letter
    {
        $key = LetterTypes::keyOf($type);

        throw_unless($type->allowedFor($user), LogicException::class, "The letter type [{$key}] is not allowed for this user.");

        $this->checkSigner($type, $key, $details->signer);

        $extraRules = Field::rules($type->extraFields(), [$letter]);

        $validated = validator($extra, $extraRules)->validate();

        return $letter->getConnection()->transaction(function () use ($letter, $type, $key, $source, $details, $validated): Letter {
            $letter->type = $key;
            $letter->source()->associate($source);
            $letter->reference_number = $details->referenceNumber;
            $letter->date = $details->date;
            $letter->carbon_copies = $details->carbonCopies;
            $letter->computer_signed = $details->computerSigned;
            $letter->with_letterhead = $details->withLetterhead || $details->computerSigned;
            $letter->save();

            $letter->extra = $this->storeImages($letter, $type->extraFields(), $validated);
            $letter->save();

            $this->syncSigner($letter, $details->signer);

            return $letter;
        });
    }

    private function checkSigner(LetterType $type, string $key, ?User $signer): void
    {
        throw_if($signer instanceof User && $type->signer() === SignerRequirement::Hidden, LogicException::class, "The letter type [{$key}] has no signer.");

        if (! $signer instanceof User) {
            throw_if($type->signer() === SignerRequirement::Required, ValidationException::withMessages(['signer' => __('letters/letters.signer_required')]));

            return;
        }

        throw_if($signer->profile?->position_id === null, ValidationException::withMessages(['signer' => __('letters/letters.signer_without_position')]));
    }

    private function syncSigner(Letter $letter, ?User $signer): void
    {
        if ($signer instanceof User) {
            $letter->act(Act::Sign, $signer);

            return;
        }

        $letter->action(Act::Sign)?->delete();
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<array-key, mixed>  $values
     * @return array<string, mixed>
     */
    private function storeImages(Letter $letter, array $fields, array $values): array
    {
        return collect($fields)
            ->mapWithKeys(fn (Field $field): array => [$field->name => $this->storeValue($letter, $field, $values[$field->name] ?? null)])
            ->all();
    }

    private function storeValue(Letter $letter, Field $field, mixed $value): mixed
    {
        return match (true) {
            $field->type === FieldType::Image && $value instanceof TemporaryUploadedFile => $this->storeUpload($letter, $value),
            $field->type === FieldType::Group && is_array($value) => $this->storeImages($letter, $field->children, $value),
            $field->type === FieldType::Table && is_array($value) => collect($value)->map(fn (mixed $row): mixed => is_array($row) ? $this->storeImages($letter, $field->children, $row) : $row)->values()->all(),
            default => $value,
        };
    }

    private function storeUpload(Letter $letter, TemporaryUploadedFile $file): string
    {
        $media = $letter->addMediaFromStream($file->readStream())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection(LetterMedia::Data->value);

        $file->delete();

        return $media->uuid;
    }
}
