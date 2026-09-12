<?php

use App\Http\Controllers\AdminAccessRequestController;
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
| Administrator Access Review
|--------------------------------------------------------------------------
|
| Protected professional-access review, decisions, and recovery actions.
| All mutations remain restricted to authenticated administrators.
|
*/

Route::middleware([
    'auth',
    'role:admin',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            '/access-requests',
            [AdminAccessRequestController::class, 'index']
        )->name('access-requests.index');


        Route::get(
            '/access-requests/{accessRequest:public_id}',
            [AdminAccessRequestController::class, 'show']
        )->name('access-requests.show');


        Route::get(
            '/access-requests/{accessRequest:public_id}/proof',
            [AdminAccessRequestController::class, 'proof']
        )
            ->middleware('throttle:admin-access-request-proof')
            ->name('access-requests.proof');


        Route::post(
            '/access-requests/{accessRequest:public_id}/approve',
            [AdminAccessRequestController::class, 'approve']
        )
            ->middleware('throttle:admin-access-request-decision')
            ->name('access-requests.approve');


        Route::post(
            '/access-requests/{accessRequest:public_id}/reject',
            [AdminAccessRequestController::class, 'reject']
        )
            ->middleware('throttle:admin-access-request-decision')
            ->name('access-requests.reject');


        Route::post(
            '/access-requests/{accessRequest:public_id}/resend-setup',
            [AdminAccessRequestController::class, 'resendSetup']
        )
            ->middleware('throttle:admin-access-request-setup-resend')
            ->name('access-requests.resend-setup');


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


/*
|--------------------------------------------------------------------------
| TEMPORARY — Render Proxy Diagnostic
|--------------------------------------------------------------------------
|
| Phase A only. Admin-authenticated production diagnostic used to confirm
| how Laravel interprets Render's forwarded HTTPS proxy headers.
| Remove immediately after the diagnosis is recorded.
|
*/

Route::get('/__diag-proxy', function (\Illuminate\Http\Request $request) {
    abort_unless(app()->environment('production'), 404);

    return response()->json([
        'scheme'            => $request->getScheme(),
        'host'              => $request->getHost(),
        'scheme_and_host'   => $request->getSchemeAndHttpHost(),
        'is_secure'         => $request->isSecure(),
        'app_url'           => config('app.url'),
        'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
        'x_forwarded_host'  => $request->header('X-Forwarded-Host'),
        'x_forwarded_port'  => $request->header('X-Forwarded-Port'),
        'x_forwarded_for'   => $request->header('X-Forwarded-For')
            ? 'present'
            : 'absent',
        'trusted_proxies'   => $request->getTrustedProxies(),
    ]);
})->middleware([
    'auth',
    'role:admin',
]);

require __DIR__.'/auth.php';