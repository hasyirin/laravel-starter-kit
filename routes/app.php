<?php

declare(strict_types=1);

use App\Http\Controllers\MediaController;
use App\Livewire\Pages\App\Dashboard;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Route;

Route::get('media/{media:uuid}/{conversion?}', MediaController::class)->name('media.show');

Route::redirect('settings', '/settings/profile')->name('settings');

Route::livewire('settings/profile', Profile::class)->name('profile.edit');

Route::middleware(['verified'])->group(function (): void {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');

    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::livewire('settings/security', Security::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
