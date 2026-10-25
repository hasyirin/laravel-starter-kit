<?php

declare(strict_types=1);

use App\Livewire\Pages\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');

Route::redirect('settings', '/settings/profile')->name('settings');
