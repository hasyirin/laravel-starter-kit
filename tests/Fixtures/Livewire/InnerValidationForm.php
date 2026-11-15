<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Form;

class InnerValidationForm extends Form
{
    public string $name = '';

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }

    public function save(): void
    {
        $this->transaction(function (): void {
            $this->validate();
        });
    }
}
