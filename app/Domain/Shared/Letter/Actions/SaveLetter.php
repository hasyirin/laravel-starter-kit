<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Actor\Enums\Act;
use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Letter\Enums\FieldType;
use App\Domain\Shared\Letter\Enums\LetterMedia;
use App\Domain\Shared\Letter\Enums\NumberingMode;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\Shared\Letter\Support\ReferenceSequence;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;
use Throwable;

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

        $this->checkSigner($letter, $type, $key, $details->signer);

        $extraRules = Field::rules($type->extraFields(), [$letter]);

        $validated = validator($extra, $extraRules)->validate();

        validator(['sequence_number' => $details->sequenceNumber], ['sequence_number' => ['nullable', 'integer', 'min:1']])->validate();

        $original = $letter->getRawOriginal();
        $exists = $letter->exists;
        $recentlyCreated = $letter->wasRecentlyCreated;

        try {
            return $this->save($letter, $type, $key, $source, $user, $details, $validated);
        } catch (Throwable $throwable) {
            $letter->setRawAttributes($original, sync: true);
            $letter->exists = $exists;
            $letter->wasRecentlyCreated = $recentlyCreated;

            throw $throwable;
        }
    }

    /**
     * @param  array<array-key, mixed>  $validated
     */
    private function save(Letter $letter, LetterType $type, string $key, Model $source, User $user, PrintDetails $details, array $validated): Letter
    {
        return $letter->getConnection()->transaction(function () use ($letter, $type, $key, $source, $user, $details, $validated): Letter {
            $letter->type = $key;
            $letter->source()->associate($source);
            $letter->date = $details->date;
            $letter->carbon_copies = $details->carbonCopies;
            $letter->computer_signed = $details->computerSigned;
            $letter->with_letterhead = $details->withLetterhead || $details->computerSigned;
            $letter->save();

            $letter->extra = $this->storeImages($letter, $type->extraFields(), $validated);
            $letter->save();

            $this->syncSigner($letter, $details->signer);

            $this->number($letter, $type, $user, $details);

            return $letter;
        });
    }

    private function number(Letter $letter, LetterType $type, User $printer, PrintDetails $details): void
    {
        $mode = $type->numbering();
        $letter->unsetRelation('signature');

        if ($mode === NumberingMode::Automatic) {
            return;
        }

        if ($mode === NumberingMode::Manual) {
            $this->storeTyped($letter, $type, $printer, $details->referenceNumber);

            return;
        }

        $number = $details->sequenceNumber;

        if ($number === null || $number === $letter->sequence_number) {
            return;
        }

        $sequenceKey = $letter->sequence_key ?? ReferenceSequence::key($type->sequenceKey($letter, $printer));
        $letter->sequence_key = $sequenceKey;
        $reference = $type->reference($number, $letter);
        $this->checkTaken($letter, $reference);

        ReferenceSequence::for($type, $sequenceKey)->reserve($number);

        $letter->sequence_number = $number;
        $letter->sequence_cycle = null;
        $this->storeReference($letter, $reference);
    }

    private function storeTyped(Letter $letter, LetterType $type, User $printer, ?string $reference): void
    {
        if ($reference === null) {
            $letter->reference_number = null;

            if ($letter->sequence_number === null) {
                $letter->sequence_key = null;
            }

            $letter->save();

            return;
        }

        $letter->sequence_key ??= ReferenceSequence::key($type->sequenceKey($letter, $printer));
        $this->checkTaken($letter, $reference);
        $this->storeReference($letter, $reference);
    }

    private function checkTaken(Letter $letter, string $reference): void
    {
        $taken = Letter::withTrashed()
            ->whereKeyNot($letter->id)
            ->where('sequence_key', $letter->sequence_key)
            ->where('reference_number', $reference)
            ->exists();

        throw_if($taken, $this->takenError());
    }

    private function storeReference(Letter $letter, string $reference): void
    {
        $letter->reference_number = $reference;

        try {
            $letter->save();
        } catch (UniqueConstraintViolationException) {
            throw $this->takenError();
        }
    }

    private function takenError(): ValidationException
    {
        return ValidationException::withMessages(['reference_number' => __('letters/letters.reference_number_taken')]);
    }

    private function checkSigner(Letter $letter, LetterType $type, string $key, ?User $signer): void
    {
        throw_if($signer instanceof User && $type->signer() === SignerRequirement::Hidden, LogicException::class, "The letter type [{$key}] has no signer.");

        if (! $signer instanceof User) {
            throw_if($type->signer() === SignerRequirement::Required, ValidationException::withMessages(['signer' => __('letters/letters.signer_required')]));

            return;
        }

        if ($letter->exists && $letter->signature?->actor_id === $signer->id) {
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
