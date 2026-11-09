<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Domain\Shared\Organization\Models\Position;
use App\Livewire\Forms\Internal\PositionForm;
use App\Livewire\RecordModal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/** @extends RecordModal<Position> */
class PositionModal extends RecordModal
{
    #[Locked]
    public Organization $organization;

    #[Locked]
    public ?Department $department = null;

    public PositionForm $form;

    public static function label(): string
    {
        return (string) __('organization/positions.position');
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        /** @var array<int, string> $departments */
        $departments = Department::query()->whereBelongsTo($this->organization)->orderBy('name')->pluck('name', 'id')->all();

        return $departments;
    }

    public function editable(string $part): bool
    {
        return ! $this->isDisabled() && $this->form->editable($part);
    }

    /**
     * @return HasMany<Position, Organization>
     */
    protected function query(): HasMany
    {
        return $this->organization->positions();
    }

    protected function fillForm(): void
    {
        $this->form->set($this->organization, $this->record, $this->department);
    }

    protected function persist(): void
    {
        $this->form->save();
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.position-modal');
    }
}
