<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

class ParentPageHookedNoteDataTable extends PlainNoteDataTable
{
    /**
     * @var array<int, mixed>
     */
    public array $hookedPageNames = [];

    public function updatedPaginators(mixed $page, mixed $pageName): void
    {
        parent::updatedPaginators($page, $pageName);

        $this->hookedPageNames[] = $pageName;
    }
}
