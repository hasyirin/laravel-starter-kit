<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

use App\Livewire\Component;
use App\Livewire\Concerns\CanBeDisabled;
use App\Livewire\Forms\Shared\AddressForm;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class Address extends Component
{
    use CanBeDisabled;

    #[Locked]
    public bool $required = false;

    #[Locked]
    public ?DistrictsMode $districts = null {
        // Livewire puts a tag attribute, such as districts="required", into the property as a string.
        set(DistrictsMode|string|null $districts) => is_string($districts) ? DistrictsMode::from($districts) : $districts;
    }

    #[Locked]
    public ?string $label = null;

    public AddressForm $address;

    public function mount(): void
    {
        $this->address->required($this->required);

        if (! $this->districts instanceof DistrictsMode) {
            return;
        }

        $this->address->withDistricts(required: $this->districts === DistrictsMode::Required);
    }

    public function save(): void
    {
        $this->address->validate();
    }

    public function render(): View
    {
        return view('livewire.pages.local.examples.address');
    }
}
