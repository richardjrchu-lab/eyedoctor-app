<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Doctor Workspace
|--------------------------------------------------------------------------
|
| Doctors land on the RETINA Overview Dashboard after authentication.
| Screening remains doctor-only.
|
*/

Route::middleware([
    'auth',
    'role:doctor',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Overview Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('welcome');


    /*
    |--------------------------------------------------------------------------
    | Screening Workspace
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/screening',
        [PredictionController::class, 'welcome']
    )->name('screening');


    Route::post(
        '/predict',
        [PredictionController::class, 'predict']
    )
        ->middleware('throttle:20,1')
        ->name('predict');


    Route::post(
        '/predictions/{prediction}/correct',
        [PredictionController::class, 'correct']
    )->name('predictions.correct');

});


/*
|--------------------------------------------------------------------------
| Shared Professional Area
|--------------------------------------------------------------------------
|
| Doctors can review their own records.
| Administrators can review all authorized records.
|
| Doctors and administrators may access the protected RETINA mobile
| application distribution page.
|
*/

Route::middleware([
    'auth',
    'role:doctor|admin',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Prediction History
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/history',
        [PredictionController::class, 'history']
    )->name('history');


    Route::get(
        '/predictions/{prediction}',
        [PredictionController::class, 'show']
    )->name('predictions.show');


    Route::get(
        '/images/{image}/file',
        [PredictionController::class, 'imageFile']
    )->name('images.file');


    /*
    |--------------------------------------------------------------------------
    | Mobile Application
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mobile-app',
        [MobileAppController::class, 'index']
    )->name('mobile-app');


    Route::get(
        '/mobile-app/download',
        [MobileAppController::class, 'download']
    )
        ->middleware('throttle:10,1')
        ->name('mobile-app.download');

});


/*
|--------------------------------------------------------------------------
| Account Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');


    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');


    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');

});


require __DIR__.'/auth.php';