<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/analyze-retina', function (Request $request) {
    if (!$request->hasFile('eye_photo')) {
        return response()->json(['error' => 'No image uploaded'], 400);
    }

    $image = $request->file('eye_photo');
    $tempPath = $image->getRealPath();
    
    // Grab the API key from your .env
    $apiKey = env('GEMINI_API_KEY', '');

    // Windows syntax: Set environment variable and run python3 script
    $command = 'set GEMINI_API_KEY=' . escapeshellarg($apiKey) . ' && python3 ' . escapeshellarg(base_path('predict.py')) . ' ' . escapeshellarg($tempPath) . ' 2>&1';
    
    $output = shell_exec($command);
    
    $decoded = json_decode(trim($output), true);

    if (!$decoded) {
        return response()->json([
            'stage' => 'invalid',
            'description' => 'Execution Error Output: ' . $output
        ]);
    }

    return response()->json($decoded);
});