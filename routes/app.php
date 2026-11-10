<?php

declare(strict_types=1);

use App\Http\Controllers\MediaController;
use App\Http\Controllers\StopImpersonationController;
use App\Livewire\Pages\App\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('media/{media:uuid}/{conversion?}', MediaController::class)->name('media.show');

Route::post('impersonation/stop', StopImpersonationController::class)->name('impersonation.stop');

Route::redirect('settings', '/settings/user-information')->name('settings');

require __DIR__.'/settings.php';

Route::middleware(['verified'])->group(function (): void {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');
});
