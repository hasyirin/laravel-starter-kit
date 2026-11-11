<?php

declare(strict_types=1);

use App\Livewire\Pages\System\AuditLog;
use App\Livewire\Pages\System\Dashboard;
use App\Livewire\Pages\System\Letters\Compose;
use App\Livewire\Pages\System\MessageLog;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::prefix('logs')->name('logs.')->group(function (): void {
    Route::livewire('audits', AuditLog::class)->name('audits');
    Route::livewire('messages', MessageLog::class)->name('messages');
});
Route::livewire('letters/compose', Compose::class)->name('letters.compose');

Route::redirect('settings', '/system/settings/user-information')->name('settings');

require __DIR__.'/settings.php';
