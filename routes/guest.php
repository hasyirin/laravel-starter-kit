<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');
