<?php

use App\Http\Controllers\LegalController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| PUBLIC ENTRY
|--------------------------------------------------------------------------
|
| Guests are sent to Login.
| Authenticated users are sent to RETINA Overview.
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('overview');
    }

    return redirect()->route('login');
})->name('welcome');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED + VERIFIED USERS
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | LEGAL DOCUMENTS
    |--------------------------------------------------------------------------
    |
    | These routes must NOT use legal.accepted because users need access
    | to them before accepting the documents.
    |
    */

    Route::get('/legal/eula', [LegalController::class, 'showEula'])
        ->name('legal.eula');

    Route::post('/legal/eula', [LegalController::class, 'acceptEula'])
        ->name('legal.eula.accept');


    Route::get('/legal/privacy', [LegalController::class, 'showPrivacy'])
        ->name('legal.privacy');

    Route::post('/legal/privacy', [LegalController::class, 'acceptPrivacy'])
        ->name('legal.privacy.accept');


    /*
    |--------------------------------------------------------------------------
    | RETINA WEB
    |--------------------------------------------------------------------------
    |
    | Everything below requires the current EULA and Privacy Notice.
    |
    */

    Route::middleware('legal.accepted')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | OVERVIEW
        |--------------------------------------------------------------------------
        */

        Route::get('/overview', function () {
            return view('overview');
        })->name('overview');


        /*
        |--------------------------------------------------------------------------
        | SCREENING
        |--------------------------------------------------------------------------
        |
        | Uses the existing welcome.blade.php in normal screening mode.
        |
        */

        Route::get('/screening', function () {
            return view('welcome', [
                'evaluationMode' => false,
            ]);
        })->name('screening');


        /*
        |--------------------------------------------------------------------------
        | FORMAL EVALUATION
        |--------------------------------------------------------------------------
        |
        | Uses the same screening workspace but enables Study Case ID mode.
        |
        */

        Route::get('/evaluation', function () {
            return view('welcome', [
                'evaluationMode' => true,
            ]);
        })->name('evaluation');


        /*
        |--------------------------------------------------------------------------
        | STANDARD PREDICTION
        |--------------------------------------------------------------------------
        */

        Route::post('/predict', [PredictionController::class, 'predict'])
            ->name('predict');


        /*
        |--------------------------------------------------------------------------
        | FORMAL EVALUATION PREDICTION
        |--------------------------------------------------------------------------
        */

        Route::post('/evaluation/predict', [PredictionController::class, 'predict'])
            ->name('evaluation.predict');


        /*
        |--------------------------------------------------------------------------
        | OLD LARAVEL DASHBOARD
        |--------------------------------------------------------------------------
        |
        | If anything still tries to open /dashboard, redirect it to
        | RETINA Overview instead of showing the default Laravel page.
        |
        */

        Route::get('/dashboard', function () {
            return redirect()->route('overview');
        })->name('dashboard');
    });
});


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| AUTHENTICATION / REQUEST ACCESS ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';