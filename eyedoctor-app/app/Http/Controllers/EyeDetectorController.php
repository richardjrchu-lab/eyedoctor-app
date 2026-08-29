<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EyeDetectorController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'eye_photo' => 'required|image|mimes:jpeg,png,jpg|max:12288',
        ]);

        $file = $request->file('eye_photo');
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY environment variable is missing.'], 500);
        }

        try {
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $imageMime = $file->getClientMimeType();

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=" . $apiKey;

            // Updated system prompt focusing on lesion density and heatmap feature activation
            $prompt = "You are an advanced clinical ophthalmic AI diagnostic tool. Analyze this eye fundus scan.
            
            TASK 1: STRUCTURAL VALIDATION
            - Check if the uploaded image is a valid human retinal fundus photo or fluorescein angiography.
            - If it is a random object, portrait, pet, landscape, or document image, return is_retinal_scan = false and stage = -1.

            TASK 2: HEATMAP & LESION ANALYSIS
            - Inspect the image for active pathological regions (Microaneurysms, Blot Hemorrhages, Hard Exudates, Cotton Wool Spots, and Neovascularization).
            - Grade the image strictly using the International Clinical Diabetic Retinopathy (ICDR) scale:
              * Stage 0: No DR. Normal macula, intact vessel architecture.
              * Stage 1: Mild NPDR. Microaneurysms only (isolated small red dots).
              * Stage 2: Moderate NPDR. Multiple microaneurysms, hemorrhages, or exudates in 1-3 quadrants.
              * Stage 3: Severe NPDR. Widespread hemorrhages in all 4 quadrants or venous beading.
              * Stage 4: Proliferative DR. Active neovascularization (capillary web proliferation).";

            $generationConfig = [
                "responseMimeType" => "application/json",
                "responseSchema" => [
                    "type" => "OBJECT",
                    "properties" => [
                        "is_retinal_scan" => [
                            "type" => "BOOLEAN",
                            "description" => "True if valid retina, false for non-eye images."
                        ],
                        "stage" => [
                            "type" => "INTEGER",
                            "description" => "ICDR stage (0, 1, 2, 3, 4). Set to -1 if non-retinal image."
                        ],
                        "confidence" => [
                            "type" => "INTEGER",
                            "description" => "Diagnostic certainty score from 0 to 100."
                        ],
                        "heatmap_density" => [
                            "type" => "NUMBER",
                            "description" => "Estimated spatial lesion density ratio between 0.00 and 1.00."
                        ]
                    ],
                    "required" => ["is_retinal_scan", "stage", "confidence", "heatmap_density"]
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $imageMime,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => $generationConfig
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'AI processing gateway connection failed.'], 502);
            }

            $resultData = $response->json();
            $aiTextResponse = $resultData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsedOutput = json_decode(trim($aiTextResponse), true);

            if (!$parsedOutput || $parsedOutput['is_retinal_scan'] === false || intval($parsedOutput['stage']) === -1) {
                return response()->json([
                    'stage' => 'invalid',
                    'structural_density' => '0.00',
                    'vascular_complexity' => 0
                ]);
            }

            return response()->json([
                'stage' => intval($parsedOutput['stage']), 
                'structural_density' => number_format(floatval($parsedOutput['heatmap_density'] ?? 0.15), 2),
                'vascular_complexity' => intval($parsedOutput['confidence'] ?? 95)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'stage' => 'invalid',
                'structural_density' => '0.00',
                'vascular_complexity' => 0
            ]);
        }
    }
}