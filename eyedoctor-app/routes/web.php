<?php

use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:doctor'])->get('/', function () {
    return view('welcome');
})->name('welcome');

Route::middleware(['auth', 'role:doctor'])->post('/predict', [PredictionController::class, 'predict'])->name('predict');
Route::middleware(['auth', 'role:doctor'])->post('/predictions/{prediction}/correct', [PredictionController::class, 'correct'])->name('predictions.correct');
Route::middleware(['auth', 'role:doctor'])->get('/history', [PredictionController::class, 'history'])->name('history');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';