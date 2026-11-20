<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

class HookedNoteDataTable extends PlainNoteDataTable
{
    /**
     * @var array<int, string>
     */
    public array $updatedProperties = [];

    public function updated(string $name, mixed $value): void
    {
        $this->updatedProperties[] = $name;
    }
}
