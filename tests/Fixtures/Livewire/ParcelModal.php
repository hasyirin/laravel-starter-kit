<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\RecordModal;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Validate;
use Tests\Fixtures\Models\Parcel;

/**
 * @extends RecordModal<Parcel>
 */
class ParcelModal extends RecordModal
{
    #[Validate('required')]
    public string $text = '';

    public static function label(): string
    {
        return 'Parcel';
    }

    /**
     * @return Builder<Parcel>
     */
    protected function query(): Builder
    {
        return Parcel::query();
    }

    protected function fillForm(): void
    {
        $this->text = $this->record->label ?? '';
    }

    protected function persist(): void
    {
        $this->record->fill(['label' => $this->text])->save();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <flux:modal wire:model="showing" data-parcel-modal></flux:modal>
            </div>
            BLADE;
    }
}
