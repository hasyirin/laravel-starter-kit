<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Organization;

use App\Domain\Shared\Letter\Models\Letterhead;
use App\Domain\Shared\Organization\Models\Branch;
use App\Domain\Shared\Organization\Models\Department;
use App\Domain\Shared\Organization\Models\Organization;
use App\Livewire\Forms\Internal\LetterheadForm;
use App\Livewire\RecordModal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/** @extends RecordModal<Letterhead> */
class LetterheadModal extends RecordModal
{
    #[Locked]
    public Organization $organization;

    public LetterheadForm $form;

    public static function label(): string
    {
        return (string) __('letters/letterheads.letterhead');
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function branches(): array
    {
        /** @var array<int, string> $branches */
        $branches = Branch::query()->whereBelongsTo($this->organization)->orderBy('name')->pluck('name', 'id')->all();

        return $branches;
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

    /**
     * @return HasMany<Letterhead, Organization>
     */
    protected function query(): HasMany
    {
        return $this->organization->letterheads();
    }

    protected function fillForm(): void
    {
        $this->form->set($this->organization, $this->record);
    }

    protected function persist(): void
    {
        $this->form->save();
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.organization.letterhead-modal');
    }
}
