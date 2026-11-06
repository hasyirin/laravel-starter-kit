<?php

declare(strict_types=1);

use App\Livewire\Pages\Admin\AuditLog;
use App\Livewire\Pages\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::livewire('audit-log', AuditLog::class)->name('audit-log');

Route::redirect('settings', '/settings/user-information')->name('settings');
