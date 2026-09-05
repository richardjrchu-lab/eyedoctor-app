<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Image;
use App\Models\Prediction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PredictionController extends Controller
{
    public function predict(Request $request)
    {
        // 1. Validate the upload before anything else touches it
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $user = $request->user();

        // 2. Build an anonymized filename -- no original filename, no patient identifiers
        $extension = $file->getClientOriginalExtension();
        $anonymizedFilename = 'anonymousimage_' . Str::random(12) . '.' . $extension;
        $storagePath = 'uploads/' . $user->id . '/' . $anonymizedFilename;

        // 3. Upload to Supabase Storage
        Storage::disk('s3')->put($storagePath, file_get_contents($file));

        // 4. Record the image in the database
        $image = Image::create([
            'user_id' => $user->id,
            'storage_path' => $storagePath,
            'anonymized_filename' => $anonymizedFilename,
            'validation_status' => 'valid',
        ]);

        AuditLog::record('uploaded_image', $image->id, 'image');

        // 5. Call FastAPI internally -- not exposed to the browser
        $response = Http::attach(
            'file', file_get_contents($file), $anonymizedFilename
        )->post(config('services.fastapi.url') . '/predict');

        if (! $response->successful()) {
            $image->update(['validation_status' => 'rejected_not_fundus']);
            return response()->json([
                'detail' => $response->json('detail') ?? 'Model server error.',
            ], $response->status());
        }

        $data = $response->json();

        // 6. Save the prediction result
        $labelToStageIndex = [
            'No DR' => 0,
            'Mild NPDR' => 1,
            'Moderate NPDR' => 2,
            'Severe NPDR' => 3,
            'PDR' => 4,
        ];

        $prediction = Prediction::create([
            'image_id' => $image->id,
            'predicted_class' => $labelToStageIndex[$data['predicted_label']] ?? 0,
            'confidence_score' => $data['confidence'] ?? 0,
            'probabilities' => $data['class_probabilities'] ?? [],
            'referral_flag' => $data['flagged_for_review'] ?? false,
            'gradcam_path' => null,
            'model_version' => 'efficientnet-b4-512-coral',
        ]);

      AuditLog::record('viewed_result', $prediction->id, 'prediction');

// 7. Return the same shape the frontend already expects, plus our DB prediction ID
$data['prediction_id'] = $prediction->id;

return response()->json($data);
    }

    public function correct(Request $request, Prediction $prediction)
    {
        $request->validate([
            'corrected_class' => 'required|integer|min:0|max:4',
            'note' => 'nullable|string|max:2000',
        ]);

        $correction = \App\Models\Correction::updateOrCreate(
            ['prediction_id' => $prediction->id],
            [
                'corrected_by' => $request->user()->id,
                'corrected_class' => $request->corrected_class,
                'note' => $request->note,
            ]
        );

        AuditLog::record('corrected_prediction', $correction->id, 'correction');

        return response()->json(['success' => true]);
    }

    public function history(Request $request)
    {
        $images = Image::with('prediction.correction')
            ->visibleTo($request->user())
            ->latest()
            ->paginate(20);

        return view('history', ['images' => $images]);
    }
}