<?php

declare(strict_types=1);

use App\Livewire\Auth\SmsPasswordReset;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('forgot-password/sms', SmsPasswordReset::class)->middleware('guest')->name('password.sms');

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');
