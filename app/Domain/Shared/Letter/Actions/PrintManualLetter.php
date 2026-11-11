<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Letter\Concerns\StoresLetterImages;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Preferences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use Throwable;

class PrintManualLetter
{
    use Actionable;
    use StoresLetterImages;

    private const string EVENT = 'printed-manual-letter';

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     */
    public function execute(LetterType $type, array $data, array $extra, PrintDetails $details, User $admin, ?Model $source = null, bool $preview = false, ?Letter $letter = null): PdfFile
    {
        $key = LetterTypes::keyOf($type);

        throw_unless($type->allowedFor($admin), LogicException::class, "The letter type [{$key}] is not allowed for this user.");
        throw_if($source instanceof Model && ! $source instanceof ($type->source()), LogicException::class, "The source is not a source of the letter type [{$key}].");

        throw_if($letter instanceof Letter && (! $source instanceof Model || $letter->type !== $key || ! $letter->source?->is($source)), LogicException::class, "The letter does not belong to the source and the letter type [{$key}].");

        $this->checkSigner($type, $key, $details->signer);

        $dataOwners = $source instanceof Model ? [$source] : [];
        $extraOwners = $letter instanceof Letter ? [...$dataOwners, $letter] : $dataOwners;

        $validatedData = $this->validated('data', $data, $type->fields(), $dataOwners);
        $validatedExtra = $this->validated('extra', $extra, $type->extraFields(), $extraOwners);

        $date = $details->date ?? CarbonImmutable::today(config()->string('system.timezone'));
        $signerProfile = $details->signer?->profile()->with(['user', 'position'])->first();

        try {
            $dataView = LetterData::from($type->fields(), $validatedData, $dataOwners);
            $extraView = LetterData::from($type->extraFields(), $validatedExtra, $extraOwners);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['letter' => __('letters/letters.data_unreadable')]);
        }

        $view = BuildLetterView::make()->execute(
            $type,
            $details->referenceNumber,
            $date,
            $signerProfile instanceof Profile ? $signerProfile : null,
            $details->carbonCopies,
            $details->withLetterhead || $details->computerSigned,
            $details->computerSigned,
            $admin,
        );

        $content = RenderPdf::make()
            ->execute($type->view(), ['data' => $dataView, 'extra' => $extraView, 'letter' => $view], new Preferences(config()->string('system.timezone'), $type->locale()), draft: $preview)
            ->generatePdfContent();

        if (! $preview) {
            $this->log($type, $key, $validatedData, $validatedExtra, $details, $admin, $source, $letter);
        }

        return new PdfFile(str($details->referenceNumber ?? 'letter')->slug()->append('.pdf')->toString(), $content);
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $values
     * @param  list<Model>  $owners
     * @return array<string, mixed>
     */
    private function validated(string $part, array $values, array $fields, array $owners): array
    {
        try {
            $validated = validator($values, Field::rules($fields, $owners))->validate();
        } catch (ValidationException $validationException) {
            throw self::prefixErrors($validationException, $part);
        }

        return collect($validated)->mapWithKeys(fn (mixed $value, int|string $key): array => [(string) $key => $value])->all();
    }

    public static function prefixErrors(ValidationException $exception, string $part): ValidationException
    {
        return ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (mixed $messages, int|string $key): array => ["$part.$key" => $messages])->all());
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

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     */
    private function log(LetterType $type, string $key, array $data, array $extra, PrintDetails $details, User $admin, ?Model $source, ?Letter $letter): void
    {
        $this->storedImages = [];

        try {
            $this->writeEntry($type, $key, $data, $extra, $details, $admin, $source, $letter);
        } catch (Throwable $throwable) {
            $this->removeStoredImages();

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     */
    private function writeEntry(LetterType $type, string $key, array $data, array $extra, PrintDetails $details, User $admin, ?Model $source, ?Letter $letter): void
    {
        DB::transaction(function () use ($type, $key, $data, $extra, $details, $admin, $source, $letter): void {
            /** @var AuditEntry $entry */
            $entry = activity()->causedBy($admin)->event(self::EVENT)->log(self::EVENT);

            $entry->properties = collect([
                'type' => $key,
                'data' => $this->storeImages($entry, 'default', $type->fields(), $data),
                'extra' => $this->storeImages($entry, 'default', $type->extraFields(), $extra),
                'details' => $details->toArray(),
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'letter_id' => $letter?->getKey(),
            ]);
            $entry->save();
        });
    }
}
