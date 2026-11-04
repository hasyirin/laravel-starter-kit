<?php

declare(strict_types=1);

use App\Livewire\Pages\Admin\Controls\Role\Create as RoleCreate;
use App\Livewire\Pages\Admin\Controls\Role\Index as RoleIndex;
use App\Livewire\Pages\Admin\Controls\Role\Show as RoleShow;
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

    Route::livewire('roles', RoleIndex::class)->name('roles.index');
    Route::livewire('roles/create', RoleCreate::class)->name('roles.create');
    Route::livewire('roles/{role}', RoleShow::class)->name('roles.show');
    Route::livewire('roles/{role}/users/{user}', Show::class)->name('roles.users.show');
});

Route::redirect('settings', '/settings/profile')->name('settings');
