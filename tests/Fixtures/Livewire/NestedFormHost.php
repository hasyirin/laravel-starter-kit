<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire;

use App\Livewire\Component;

class NestedFormHost extends Component
{
    public NestedForm $form;

    public function mount(): void
    {
        $this->form->set();
    }

    public function save(): void
    {
        $this->form->save();
    }

    public function refuse(): void
    {
        $this->form->refuse();
    }

    public function render(): string
    {
        return '<div>@error(\'form.inner.name\'){{ $message }}@enderror</div>';
    }
}
