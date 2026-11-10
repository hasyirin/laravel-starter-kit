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
use App\Domain\User\Models\User;
use App\Livewire\Concerns\HandlesLetterFields;
use App\Livewire\Form;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

class LetterForm extends Form
{
    use HandlesLetterFields;

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
        $signerRules = $this->editable('signer') ? ['nullable', 'integer', Rule::in(array_keys($this->signers()))] : [];

        return [
            'type' => ['required', Rule::in(array_keys($this->types()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'sequence_number' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date'],
            'signer_id' => $signerRules,
            ...$this->copyRowRules($this->editable('carbon_copies')),
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
        return $this->signerOptions($this->letterType());
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

    /**
     * @return list<Model>
     */
    protected function mediaOwners(): array
    {
        return [$this->letter];
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
        return $this->copyRows($this->letter->exists ? $this->letter->carbon_copies : []);
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

        return $this->copiesFromRows($this->carbon_copies);
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
