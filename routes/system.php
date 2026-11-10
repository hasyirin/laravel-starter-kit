<?php

declare(strict_types=1);

use App\Livewire\Pages\System\AuditLog;
use App\Livewire\Pages\System\Dashboard;
use App\Livewire\Pages\System\Letters\Compose;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::livewire('audit-log', AuditLog::class)->name('audit-log');
Route::livewire('letters/compose', Compose::class)->name('letters.compose');

Route::redirect('settings', '/system/settings/user-information')->name('settings');

require __DIR__.'/settings.php';
