<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\District;
use App\Domain\Shared\Geography\Models\Subdistrict;
use App\Livewire\Forms\Internal\SubdistrictForm;
use App\Livewire\RecordModal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/** @extends RecordModal<Subdistrict> */
class SubdistrictModal extends RecordModal
{
    #[Locked]
    public ?District $district = null;

    public SubdistrictForm $form;

    public static function label(): string
    {
        return (string) __('geography/subdistricts.subdistrict');
    }

    /**
     * @return Builder<Subdistrict>
     */
    protected function query(): Builder
    {
        $query = Subdistrict::query();

        if ($this->district instanceof District) {
            return $query->where('district_id', $this->district->id);
        }

        return $query;
    }

    protected function fillForm(): void
    {
        $this->form->set($this->parent(), $this->record);
    }

    protected function persist(): void
    {
        $this->form->save();
    }

    private function parent(): District
    {
        if ($this->district instanceof District) {
            return $this->district;
        }

        abort_unless($this->record->exists, 404);

        return District::query()->findOrFail($this->record->district_id);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.subdistrict-modal');
    }
}
