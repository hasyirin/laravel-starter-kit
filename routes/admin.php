<?php

declare(strict_types=1);

use App\Livewire\Pages\Admin\Controls\User\Create;
use App\Livewire\Pages\Admin\Controls\User\Index;
use App\Livewire\Pages\Admin\Controls\User\Show;
use App\Livewire\Pages\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');

Route::prefix('controls')->name('controls.')->group(function (): void {
    Route::livewire('users', Index::class)->name('users.index');
    Route::livewire('users/create', Create::class)->name('users.create');
    Route::livewire('users/{user}', Show::class)->name('users.show');
});

Route::redirect('settings', '/settings/profile')->name('settings');
