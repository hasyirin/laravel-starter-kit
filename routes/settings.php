<?php

declare(strict_types=1);

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Preferences;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\UserInformation;
use Illuminate\Support\Facades\Route;

Route::livewire('settings/user-information', UserInformation::class)->name('user-information.edit');

Route::livewire('settings/preferences', Preferences::class)->name('preferences.edit');

Route::middleware(['verified', 'phone.verified'])->group(function (): void {
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::livewire('settings/security', Security::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
