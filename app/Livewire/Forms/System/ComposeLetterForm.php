<?php

declare(strict_types=1);

namespace App\Livewire\Forms\System;

use App\Domain\Shared\Letter\Actions\PrintManualLetter;
use App\Domain\Shared\Letter\Enums\SignerRequirement;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Support\Field;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\Shared\Letter\Support\LetterTypes;
use App\Domain\Shared\Letter\Support\PdfFile;
use App\Domain\Shared\Letter\Support\PrintDetails;
use App\Domain\User\Models\User;
use App\Livewire\Concerns\HandlesLetterFields;
use App\Livewire\Form;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class ComposeLetterForm extends Form
{
    use HandlesLetterFields;

    #[Locked]
    public ?Model $source = null;

    #[Locked]
    public ?Letter $letter = null;

    public string $type = '';

    public string $reference_number = '';

    public ?CarbonImmutable $date = null;

    public ?int $signer_id = null;

    /** @var list<array{kind: string, target: int|string|null, text: string}> */
    public array $carbon_copies = [];

    public bool $with_letterhead = false;

    public bool $computer_signed = false;

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed> */
    public array $extra = [];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'signer_id' => ['nullable', 'integer', Rule::in(array_keys($this->signers()))],
            ...$this->copyRowRules(true),
            'with_letterhead' => ['boolean'],
            'computer_signed' => ['boolean'],
        ];
    }

    public function letterType(): ?LetterType
    {
        return $this->types()[$this->type] ?? null;
    }

    /**
     * @return array<string, LetterType>
     */
    public function types(): array
    {
        $admin = $this->printer();

        return collect(LetterTypes::all())
            ->filter(fn (LetterType $type): bool => $type->allowedFor($admin))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function signers(): array
    {
        return $this->signerOptions($this->letterType());
    }

    public function startBlank(): void
    {
        $type = $this->letterType();

        $this->source = null;
        $this->letter = null;
        $this->fillPrintDetails(null);
        $this->data = $this->formValues($type?->fields() ?? [], []);
        $this->extra = $this->formValues($type?->extraFields() ?? [], []);
    }

    public function startFrom(Model $source, ?Letter $letter = null): void
    {
        $type = $this->letterType();

        abort_unless($type instanceof LetterType && $source instanceof ($type->source()), 404);
        $this->authorize('view', $source);

        $this->source = $source;
        $this->letter = $letter;
        $this->fillPrintDetails($letter);

        $date = $this->date ?? CarbonImmutable::today(config()->string('system.timezone'));
        $this->data = $this->formValues($type->fields(), $type->letterData($source, $date)->toArray());
        $this->extra = $this->formValues($type->extraFields(), $letter->extra ?? []);
    }

    public function print(bool $preview): PdfFile
    {
        $this->validate();

        $type = $this->letterType();
        assert($type instanceof LetterType);

        if ($this->source instanceof Model) {
            $this->authorize('view', $this->source);
        }

        $data = $this->payloadOf('data', $type->fields(), $this->data);
        $extra = $this->payloadOf('extra', $type->extraFields(), $this->extra);

        try {
            return PrintManualLetter::make()->execute(
                type: $type,
                data: $data,
                extra: $extra,
                details: $this->details($type),
                admin: $this->printer(),
                source: $this->source,
                preview: $preview,
                letter: $this->letter,
            );
        } catch (ValidationException $validationException) {
            throw $this->withFormKeys($validationException);
        }
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    /**
     * @return list<Model>
     */
    protected function mediaOwners(): array
    {
        $owners = collect([$this->source, $this->letter])
            ->filter()
            ->all();

        return array_values($owners);
    }

    private function fillPrintDetails(?Letter $letter): void
    {
        $signerId = $letter?->signature?->actor_id;

        $this->reference_number = $letter->reference_number ?? '';
        $this->date = $letter->date ?? CarbonImmutable::today(config()->string('system.timezone'));
        $this->signer_id = $signerId !== null && array_key_exists($signerId, $this->signers()) ? $signerId : null;
        $this->carbon_copies = $this->copyRows($letter->carbon_copies ?? []);
        $this->with_letterhead = $letter->with_letterhead ?? false;
        $this->computer_signed = $letter->computer_signed ?? false;
    }

    private function details(LetterType $type): PrintDetails
    {
        return new PrintDetails(
            referenceNumber: $this->reference_number !== '' ? $this->reference_number : null,
            sequenceNumber: null,
            date: $this->date,
            signer: $this->signer($type),
            carbonCopies: $this->copiesFromRows($this->carbon_copies),
            withLetterhead: $type->offersLetterhead() && $this->with_letterhead,
            computerSigned: $type->offersComputerSigned() && $this->computer_signed,
        );
    }

    private function signer(LetterType $type): ?User
    {
        if ($this->signer_id === null || $type->signer() === SignerRequirement::Hidden) {
            return null;
        }

        return User::query()->findOrFail($this->signer_id);
    }

    /**
     * @param  list<Field>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function payloadOf(string $part, array $fields, array $values): array
    {
        try {
            return $this->payload($fields, $values);
        } catch (ValidationException $validationException) {
            throw $this->withFormKeys($this->prefixErrors($validationException, $part));
        }
    }

    private function prefixErrors(ValidationException $exception, string $part): ValidationException
    {
        $messages = collect($exception->errors())->mapWithKeys(fn (mixed $errors, int|string $key): array => ["$part.$key" => $errors]);

        return ValidationException::withMessages($messages->all());
    }

    private function withFormKeys(ValidationException $exception): ValidationException
    {
        $messages = collect($exception->errors())->mapWithKeys(fn (mixed $errors, int|string $key): array => [
            match (true) {
                $key === 'signer' => 'form.signer_id',
                $key === 'letter' => 'form.type',
                default => "form.$key",
            } => $errors,
        ]);

        return ValidationException::withMessages($messages->all());
    }
}
