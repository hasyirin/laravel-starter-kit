<?php

declare(strict_types=1);

use App\Livewire\Pages\System\ApiClient\Create as ApiClientCreate;
use App\Livewire\Pages\System\ApiClient\Index as ApiClientIndex;
use App\Livewire\Pages\System\ApiClient\Show as ApiClientShow;
use App\Livewire\Pages\System\ApiLog;
use App\Livewire\Pages\System\AuditLog;
use App\Livewire\Pages\System\Dashboard;
use App\Livewire\Pages\System\Letters\Compose;
use App\Livewire\Pages\System\MessageLog;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');
Route::prefix('logs')->name('logs.')->group(function (): void {
    Route::livewire('audits', AuditLog::class)->name('audits');
    Route::livewire('messages', MessageLog::class)->name('messages');
    Route::livewire('api', ApiLog::class)->name('api');
});
Route::livewire('letters/compose', Compose::class)->name('letters.compose');
Route::livewire('api-clients', ApiClientIndex::class)->name('api-clients.index');
Route::livewire('api-clients/create', ApiClientCreate::class)->name('api-clients.create');
Route::livewire('api-clients/{apiClient}', ApiClientShow::class)->name('api-clients.show');

require __DIR__.'/settings.php';
