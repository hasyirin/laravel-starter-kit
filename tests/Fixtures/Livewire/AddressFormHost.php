<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;
use App\Livewire\Forms\Shared\AddressForm;
use Livewire\Attributes\Locked;
use Tests\Fixtures\Models\Venue;

class AddressFormHost extends Component
{
    #[Locked]
    public Venue $venue;

    public AddressForm $address;

    public function mount(?Venue $venue = null, ?string $type = null, bool $required = false, bool $create = false, ?string $districts = null, bool $local = false): void
    {
        $this->venue = $venue ?? Venue::query()->create(['name' => 'Dewan Besar']);
        $this->address->required($required)->local($local)->type($type ?? 'primary');

        if ($districts !== null) {
            $this->address->withDistricts(required: $districts === 'required');
        }

        if (! $create) {
            $this->address->set($this->venue);
        }
    }

    public function save(): void
    {
        $this->address->save();
    }

    public function create(): void
    {
        $this->address->save($this->venue);
    }

    public function copyFrom(int $venueId): void
    {
        $this->address->copy(Venue::query()->findOrFail($venueId)->address);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                <x-address :form="$address" />
            </div>
            BLADE;
    }
}
