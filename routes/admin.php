<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn (): string => '')->name('dashboard');

Route::redirect('settings', '/settings/profile')->name('settings');
