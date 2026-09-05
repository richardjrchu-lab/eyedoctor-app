<?php

use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Upload and prediction -- doctors only. Admins review, they don't diagnose.
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');

    Route::post('/predict', [PredictionController::class, 'predict'])
        ->middleware('throttle:20,1')
        ->name('predict');

    Route::post('/predictions/{prediction}/correct', [PredictionController::class, 'correct'])
        ->name('predictions.correct');
});

// Review -- doctors see their own records, admins see all.
Route::middleware(['auth', 'role:doctor|admin'])->group(function () {
    Route::get('/history', [PredictionController::class, 'history'])->name('history');
    Route::get('/predictions/{prediction}', [PredictionController::class, 'show'])->name('predictions.show');
    Route::get('/images/{image}/file', [PredictionController::class, 'imageFile'])->name('images.file');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';