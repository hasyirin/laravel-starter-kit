<?php

declare(strict_types=1);

use App\Http\Controllers\Mail\FollowMailLinkController;
use App\Http\Controllers\Mail\OpenMailController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('.well-known/passkey-endpoints', fn () => response()->json([
    'enroll' => route('security.edit'),
    'manage' => route('security.edit'),
]))->name('well-known.passkeys');

Route::get('mail/open/{token}', OpenMailController::class)->middleware('throttle:mail-tracking')->name('mail.open');
Route::get('mail/link/{token}', FollowMailLinkController::class)->middleware('throttle:mail-tracking')->name('mail.link');
