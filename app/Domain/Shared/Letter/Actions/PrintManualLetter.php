<?php

declare(strict_types=1);

namespace App\Domain\Shared\Letter\Actions;

use App\Domain\Shared\Common\Concerns\Actions\Actionable;
use App\Domain\Shared\Common\Models\AuditEntry;
use App\Domain\Shared\Common\Support\AuditLog;
use App\Domain\Shared\Letter\Concerns\StoresLetterImages;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterData;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\LetterView;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\Shared\Media\Enums\MediaCollection;
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
            type: $type,
            referenceNumber: $details->referenceNumber,
            date: $date,
            signerProfile: $signerProfile instanceof Profile ? $signerProfile : null,
            carbonCopies: $details->carbonCopies,
            withLetterhead: $details->withLetterhead || $details->computerSigned,
            computerSigned: $details->computerSigned,
            printer: $admin,
        );

        $content = RenderPdf::make()
            ->execute($type->view(), ['data' => $dataView, 'extra' => $extraView, 'letter' => $view], new Preferences(config()->string('system.timezone'), $type->locale()), draft: $preview)
            ->generatePdfContent();

        if (! $preview) {
            $this->log(
                type: $type,
                key: $key,
                data: $validatedData,
                extra: $validatedExtra,
                details: $this->loggedDetails($details, $signerProfile, $view),
                admin: $admin,
                source: $source,
                letter: $letter,
            );
        }

        return PdfFile::named($details->referenceNumber, 'letter', $content);
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
            throw $this->prefixErrors($validationException, $part);
        }

        return collect($validated)->mapWithKeys(fn (mixed $value, int|string $key): array => [(string) $key => $value])->all();
    }

    private function prefixErrors(ValidationException $exception, string $part): ValidationException
    {
        $messages = collect($exception->errors())->mapWithKeys(fn (mixed $messages, int|string $key): array => ["$part.$key" => $messages]);

        return ValidationException::withMessages($messages->all());
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
     * @return array<string, mixed>
     */
    private function loggedDetails(PrintDetails $details, ?Profile $signerProfile, LetterView $view): array
    {
        $letterhead = match (true) {
            $view->letterhead instanceof Letterhead => ['id' => $view->letterhead->id, 'name' => $view->letterhead->name],
            $view->letterhead instanceof LetterheadView => ['view' => $view->letterhead->view],
            default => null,
        };

        return [
            ...$details->toArray(),
            'signerProfileId' => $signerProfile?->id,
            'signerPosition' => $view->signerPosition,
            'letterhead' => $letterhead,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $details
     */
    private function log(LetterType $type, string $key, array $data, array $extra, array $details, User $admin, ?Model $source, ?Letter $letter): void
    {
        $this->storedImages = [];

        try {
            $this->writeEntry(
                type: $type,
                key: $key,
                data: $data,
                extra: $extra,
                details: $details,
                admin: $admin,
                source: $source,
                letter: $letter,
            );
        } catch (Throwable $throwable) {
            $this->removeStoredImages();

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $details
     */
    private function writeEntry(LetterType $type, string $key, array $data, array $extra, array $details, User $admin, ?Model $source, ?Letter $letter): void
    {
        DB::transaction(function () use ($type, $key, $data, $extra, $details, $admin, $source, $letter): void {
            $entry = new AuditEntry;
            $entry->log_name = config()->string('activitylog.default_log_name');
            $entry->event = self::EVENT;
            $entry->description = self::EVENT;
            $entry->causer()->associate($admin);
            $entry->save();

            $entry->properties = AuditLog::context()->merge([
                'type' => $key,
                'data' => $this->storeImages($entry, MediaCollection::LetterImages, $type->fields(), $data),
                'extra' => $this->storeImages($entry, MediaCollection::LetterImages, $type->extraFields(), $extra, $letter),
                'details' => $details,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'letter_id' => $letter?->getKey(),
            ]);
            $entry->save();
        });
    }
}
