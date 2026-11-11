<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Domain\Shared\Actor\Models\Action;
use App\Domain\Shared\Letter\Actions\ResolveLetterhead;
use App\Domain\Shared\Letter\Models\Letter;
use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Letter\Support\LetterheadView;
use App\Domain\Shared\Letter\Support\LetterType;
use App\Domain\User\Models\User;
use App\Domain\User\Support\Placement;
use App\Livewire\Concerns\ListsCarbonCopyTargets;
use App\Livewire\Forms\Shared\LetterForm;
use App\Livewire\RecordModal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

/** @extends RecordModal<Letter> */
class LetterModal extends RecordModal
{
    use ListsCarbonCopyTargets;

    #[Locked]
    public Model $source;

    public LetterForm $form;

    public static function scope(Model $source): string
    {
        $key = (string) $source->getKey();

        return "{$source->getMorphClass()}:{$key}";
    }

    public static function scopedEvent(Model $source): string
    {
        return static::event().':'.static::scope($source);
    }

    public static function label(): string
    {
        return (string) __('letters/letters.letter');
    }

    #[On('show')]
    public function show(int|string|null $id = null, ?string $scope = null): void
    {
        if ($scope !== null && $scope !== static::scope($this->source)) {
            return;
        }

        parent::show($id);
    }

    public function isDisabled(): bool
    {
        if ($this->disabled === true) {
            return true;
        }

        if (! $this->showing) {
            return false;
        }

        if (! $this->record->exists) {
            return $this->cannot('create', $this->record);
        }

        return $this->cannot('update', $this->record) && $this->cannot('print', $this->record);
    }

    public function editable(string $part): bool
    {
        return ! $this->isDisabled() && $this->form->editable($part);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function types(): array
    {
        return collect($this->form->types())
            ->map(fn (LetterType $type): string => $type->label())
            ->all();
    }

    #[Computed]
    public function letterType(): ?LetterType
    {
        return $this->form->letterType();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function signers(): array
    {
        return $this->form->signers();
    }

    #[Computed]
    public function letterheadName(): string
    {
        $type = $this->letterType;
        /** @var User $printer */
        $printer = auth()->user();

        if (! $type instanceof LetterType) {
            return (string) __('letters/letters.no_letterhead');
        }

        $letterhead = ResolveLetterhead::make()->execute($type, $this->signerPlacement(), Placement::of($printer->profile));

        return match (true) {
            $letterhead instanceof Letterhead => $letterhead->name,
            ! $letterhead instanceof LetterheadView => (string) __('letters/letters.no_letterhead'),
            default => (string) __('letters/letters.type_letterhead'),
        };
    }

    public function updatedFormType(): void
    {
        $this->form->chooseType();
    }

    public function updatedFormComputerSigned(): void
    {
        if ($this->form->computer_signed) {
            $this->form->with_letterhead = true;
        }
    }

    /**
     * @return MorphMany<Letter, Model>
     */
    protected function query(): MorphMany
    {
        return $this->source->morphMany(Letter::class, 'source');
    }

    protected function fillForm(): void
    {
        $this->form->set($this->source, $this->record);
        unset($this->letterType, $this->types, $this->signers);
    }

    protected function persist(): void
    {
        $this->form->save();

        $this->dispatch(static::scopedEvent($this->source));
    }

    private function signerPlacement(): ?Placement
    {
        $signerId = $this->form->signer_id;

        if ($signerId === null) {
            return null;
        }

        $signature = $this->record->exists ? $this->record->signature : null;

        if ($signature instanceof Action && $signature->actor_id === $signerId) {
            return Placement::of($signature->profile()->withTrashed()->first());
        }

        if (! array_key_exists($signerId, $this->signers)) {
            return null;
        }

        return Placement::of(User::query()->find($signerId)?->profile);
    }

    public function render(): View
    {
        return view('livewire.components.letter-modal');
    }
}
