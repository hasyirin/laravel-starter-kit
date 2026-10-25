<?php

declare(strict_types=1);

use App\Livewire\Pages\Local\ComponentDocs;
use Illuminate\Support\Facades\Route;

Route::livewire('docs/components/{page?}', ComponentDocs::class)->name('docs.components');
