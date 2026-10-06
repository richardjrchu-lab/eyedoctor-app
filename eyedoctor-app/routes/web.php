<?php

use App\Http\Controllers\AdminAccessRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RetentionTriggerController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public RETINA Information
|--------------------------------------------------------------------------
|
| Public, non-clinical information intended for search discovery.
| No patient data, predictions, study cases, or authenticated records
| are exposed through this route.
|
*/

Route::view(
    '/about',
    'public.about'
)->name('public.about');

/*
|--------------------------------------------------------------------------
| Legal Onboarding
|--------------------------------------------------------------------------
|
| These routes require authentication but intentionally do NOT use the
| legal.accepted middleware.
|
| A user who has not yet accepted the current EULA or Privacy Notice must
| still be able to open and submit these pages. Protecting these routes with
| legal.accepted would create a redirect loop.
|
*/

Route::middleware('auth')->group(function () {

    Route::get(
        '/legal/eula',
        [LegalController::class, 'showEula']
    )->name('legal.eula');

    Route::post(
        '/legal/eula',
        [LegalController::class, 'acceptEula']
    )->name('legal.eula.accept');

    Route::get(
        '/legal/privacy',
        [LegalController::class, 'showPrivacy']
    )->name('legal.privacy');

    Route::post(
        '/legal/privacy',
        [LegalController::class, 'acceptPrivacy']
    )->name('legal.privacy.accept');

});

/*
|--------------------------------------------------------------------------
| Doctor Workspace
|--------------------------------------------------------------------------
|
| Doctors land on the RETINA Overview Dashboard after authentication.
| Screening remains doctor-only.
|
| The legal.accepted middleware ensures that an authenticated doctor has
| accepted the current EULA and Privacy Notice before accessing RETINA's
| clinical workspace.
|
*/

Route::middleware([
    'auth',
    'legal.accepted',
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

    /*
    |--------------------------------------------------------------------------
    | Formal Evaluation Workspace
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/evaluation',
        [PredictionController::class, 'evaluation']
    )->name('evaluation');

    Route::post(
        '/predict',
        [PredictionController::class, 'predict']
    )
        ->middleware('throttle:20,1')
        ->name('predict');

    Route::post(
        '/evaluation/predict',
        [PredictionController::class, 'predictEvaluation']
    )
        ->middleware('throttle:20,1')
        ->name('evaluation.predict');

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
| This administrative identity-management area intentionally remains
| independent of the clinical legal-acceptance middleware. An administrator
| can therefore continue reviewing professional access requests even when
| RETINA's clinical-use legal documents have changed.
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
| Doctors and administrators may access the protected RETINA application
| distribution page.
|
| Because these resources contain clinical records or provide RETINA
| application access, the current legal documents must be accepted first.
|
*/

Route::middleware([
    'auth',
    'legal.accepted',
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
    | RETINA Application Distribution
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mobile-app',
        [MobileAppController::class, 'index']
    )->name('mobile-app');

    /*
    |--------------------------------------------------------------------------
    | Android Download
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mobile-app/download',
        [MobileAppController::class, 'download']
    )
        ->middleware('throttle:10,1')
        ->name('mobile-app.download');

    /*
    |--------------------------------------------------------------------------
    | Windows Download
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mobile-app/download/windows',
        [MobileAppController::class, 'downloadWindows']
    )
        ->middleware('throttle:10,1')
        ->name('mobile-app.download.windows');

});

/*
|--------------------------------------------------------------------------
| Account Profile
|--------------------------------------------------------------------------
|
| Account-management routes deliberately remain available to authenticated
| users without legal.accepted.
|
| This preserves an account-management escape path if legal onboarding ever
| becomes unavailable or a document configuration is accidentally broken.
|
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
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Internal Image Retention Trigger
|--------------------------------------------------------------------------
|
| Machine-to-machine endpoint used only by the production retention
| scheduler.
|
| Authentication is performed by RetentionTriggerController using the
| dedicated retention secret. This endpoint must remain independent of
| browser authentication and legal onboarding.
|
*/

Route::post(
    '/internal/retention/purge',
    RetentionTriggerController::class
)
    ->withoutMiddleware([
        ValidateCsrfToken::class,
    ])
    ->middleware('throttle:retention-trigger')
    ->name('internal.retention.purge');