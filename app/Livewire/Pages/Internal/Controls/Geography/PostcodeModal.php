<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Internal\Controls\Geography;

use App\Domain\Shared\Geography\Models\Postcode;
use App\Domain\Shared\Geography\Models\PostOffice;
use App\Livewire\Forms\Internal\PostcodeForm;
use App\Livewire\RecordModal;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/** @extends RecordModal<Postcode> */
class PostcodeModal extends RecordModal
{
    #[Locked]
    public ?PostOffice $postOffice = null;

    public PostcodeForm $form;

    public static function label(): string
    {
        return (string) __('geography/postcodes.postcode');
    }

    /**
     * @return Builder<Postcode>
     */
    protected function query(): Builder
    {
        $query = Postcode::query();

        if ($this->postOffice instanceof PostOffice) {
            return $query->where('post_office_id', $this->postOffice->id);
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

    private function parent(): PostOffice
    {
        if ($this->postOffice instanceof PostOffice) {
            return $this->postOffice;
        }

        abort_unless($this->record->exists, 404);

        return PostOffice::query()->findOrFail($this->record->post_office_id);
    }

    public function render(): View
    {
        return view('livewire.pages.internal.controls.geography.postcode-modal');
    }
}
