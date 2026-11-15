<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Form;

class NestedForm extends Form
{
    public InnerValidationForm $inner;

    public function set(): static
    {
        $this->inner = new InnerValidationForm($this->component, $this->getPropertyName().'.inner');

        return $this;
    }

    public function save(): void
    {
        $this->transaction(function (): void {
            $this->inner->save();
        });
    }

    public function refuse(): void
    {
        $this->transaction(function (): void {
            $this->inner->refuse();
        });
    }
}
