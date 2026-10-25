<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

require __DIR__.'/guest.php';

Route::middleware(['auth'])->group(__DIR__.'/app.php');

Route::middleware(['auth', 'verified', 'can:'.config()->string('system.permissions.internal.access')])
    ->prefix('internal')
    ->name('internal.')
    ->group(__DIR__.'/internal.php');

Route::middleware(['auth', 'verified', 'can:'.config()->string('system.permissions.admin.access')])
    ->prefix('admin')
    ->name('admin.')
    ->group(__DIR__.'/admin.php');
