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
| Screening and formal evaluation remain doctor-only.
|
| The legal.accepted middleware ensures that an authenticated doctor has
| accepted the current RETINA Web EULA and Privacy Notice before accessing
| the Web clinical workspace.
|
*/

Route::middleware([
    'auth',
    'legal.accepted',
    'role:doctor',
])->group(function () {

    Route::get(
        '/',
        [DashboardController::class, 'index']
    )->name('welcome');

    Route::get(
        '/screening',
        [PredictionController::class, 'welcome']
    )->name('screening');

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
| independent of the RETINA Web clinical legal-acceptance middleware.
| Administrators can therefore continue reviewing professional access
| requests even when RETINA Web legal documents have changed.
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
| Shared RETINA Web Clinical Area
|--------------------------------------------------------------------------
|
| Doctors can review their own RETINA Web records.
| Administrators can review all authorized RETINA Web records.
|
| These routes expose Web clinical records and therefore require acceptance
| of the current RETINA Web EULA and Privacy Notice.
|
*/

Route::middleware([
    'auth',
    'role:doctor|admin',
    'legal.accepted',
])->group(function () {

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

});


/*
|--------------------------------------------------------------------------
| RETINA Application Distribution
|--------------------------------------------------------------------------
|
| Android and Windows are separate RETINA implementations rather than
| features of RETINA Web.
|
| Distribution remains limited to authenticated doctors and administrators,
| but access does not depend on acceptance of the RETINA Web-specific EULA
| or Privacy Notice.
|
*/

Route::middleware([
    'auth',
    'role:doctor|admin',
])->group(function () {

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
| Users may review and update their account information.
|
| Self-service hard account deletion is intentionally unavailable because
| user records are referenced by RETINA clinical, audit, and legal records.
| Account access and lifecycle changes must therefore be handled through an
| administrative process rather than destructive self-service deletion.
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