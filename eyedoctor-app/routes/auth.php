<?php

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\VerifyAccessRequestEmailController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    /*
     * Open self-registration remains deliberately disabled.
     *
     * Professional access uses a separate reviewed application workflow.
     * Submitting this form never creates a user account or grants a role.
     */

    Route::get(
        'request-access',
        [AccessRequestController::class, 'create']
    )
        ->middleware('throttle:30,1')
        ->name('access-request.create');


    Route::post(
        'request-access',
        [AccessRequestController::class, 'store']
    )
        ->middleware('throttle:5,60')
        ->name('access-request.store');


    Route::get(
        'request-access/received',
        [AccessRequestController::class, 'received']
    )
        ->middleware('throttle:30,1')
        ->name('access-request.received');


    Route::get(
        'login',
        [AuthenticatedSessionController::class, 'create']
    )->name('login');


    Route::post(
        'login',
        [AuthenticatedSessionController::class, 'store']
    );


    Route::get(
        'forgot-password',
        [PasswordResetLinkController::class, 'create']
    )->name('password.request');


    Route::post(
        'forgot-password',
        [PasswordResetLinkController::class, 'store']
    )->name('password.email');


    Route::get(
        'reset-password/{token}',
        [NewPasswordController::class, 'create']
    )->name('password.reset');


    Route::post(
        'reset-password',
        [NewPasswordController::class, 'store']
    )->name('password.store');
});



/*
 * Professional-access email verification.
 *
 * These routes are intentionally outside the normal User email-verification
 * flow because an applicant is not a User until an administrator approves
 * the request.
 *
 * Possession of a valid, unexpired Laravel-signed URL is required before
 * the request can move from email_pending to pending_review.
 */

Route::get(
    'request-access/verify/{publicId}/{emailHash}',
    VerifyAccessRequestEmailController::class
)
    ->middleware([
        'signed',
        'throttle:6,1',
    ])
    ->name('access-request.verify');


Route::view(
    'request-access/email-verified',
    'auth.access-request-email-verified'
)
    ->middleware('throttle:30,1')
    ->name('access-request.email-verified');


Route::middleware('auth')->group(function () {
    Route::get(
        'verify-email',
        EmailVerificationPromptController::class
    )->name('verification.notice');


    Route::get(
        'verify-email/{id}/{hash}',
        VerifyEmailController::class
    )
        ->middleware([
            'signed',
            'throttle:6,1',
        ])
        ->name('verification.verify');


    Route::post(
        'email/verification-notification',
        [EmailVerificationNotificationController::class, 'store']
    )
        ->middleware('throttle:6,1')
        ->name('verification.send');


    Route::get(
        'confirm-password',
        [ConfirmablePasswordController::class, 'show']
    )->name('password.confirm');


    Route::post(
        'confirm-password',
        [ConfirmablePasswordController::class, 'store']
    );


    Route::put(
        'password',
        [PasswordController::class, 'update']
    )->name('password.update');


    Route::post(
        'logout',
        [AuthenticatedSessionController::class, 'destroy']
    )->name('logout');
});
