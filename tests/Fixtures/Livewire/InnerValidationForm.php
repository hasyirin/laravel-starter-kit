<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Form;
use Illuminate\Validation\ValidationException;

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

    public function saveName(): void
    {
        $this->transaction(function (): void {
            $this->validateOnly('name');
        });
    }

    public function refuse(): void
    {
        $this->transaction(fn (): never => throw ValidationException::withMessages(['name' => 'The name is taken.']));
    }
}
