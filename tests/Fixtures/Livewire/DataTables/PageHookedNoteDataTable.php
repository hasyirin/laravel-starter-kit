<?php

declare(strict_types=1);

namespace Tests\Fixtures\Livewire\DataTables;

class PageHookedNoteDataTable extends PlainNoteDataTable
{
    /**
     * @var array<int, mixed>
     */
    public array $hookedPageNames = [];

    /**
     * @param  mixed  $page
     * @param  mixed  $pageName
     */
    public function updatedPaginators($page, $pageName): void
    {
        $this->hookedPageNames[] = $pageName;
    }
}
