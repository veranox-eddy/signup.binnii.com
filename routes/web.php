<?php

use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;

// The marketing site is the separate static binnii.com repo — this app
// only owns the signup flow.
Route::get('/', fn () => redirect()->route('signup.account'));

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/signup', [SignupController::class, 'account'])->name('signup.account');
    Route::post('/signup', [SignupController::class, 'storeAccount'])->name('signup.account.store');
    Route::get('/signup/organization', [SignupController::class, 'organization'])->name('signup.organization');
    Route::post('/signup/organization', [SignupController::class, 'store'])->name('signup.organization.store');
    Route::get('/signup/check-email', [SignupController::class, 'checkEmail'])->name('signup.check-email');
    Route::post('/signup/resend', [SignupController::class, 'resend'])->name('signup.resend');
});

Route::get('/signup/verify/{token}', [SignupController::class, 'verify'])
    ->middleware('throttle:10,1')->name('signup.verify');

// Waiting page: signed URL (30 min) so nobody can enumerate other
// people's status via bare uuids.
Route::get('/signup/activating/{uuid}', [App\Http\Controllers\ActivationController::class, 'show'])
    ->middleware(['signed', 'throttle:120,1'])->name('signup.activating');
