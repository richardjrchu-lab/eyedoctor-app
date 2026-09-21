<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Correction;
use App\Models\Image;
use App\Models\Prediction;
use App\Services\ImageSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PredictionController extends Controller
{
    /**
     * ICDR stage index for each label the model can return.
     */
    private const STAGE_INDEX = [
        'No DR' => 0,
        'Mild NPDR' => 1,
        'Moderate NPDR' => 2,
        'Severe NPDR' => 3,
        'PDR' => 4,
    ];

    public function predictEvaluation(
        Request $request,
        ImageSanitizer $imageSanitizer
    ) {
        $request->attributes->set(
            'evaluation_mode',
            true
        );

        return $this->predict(
            $request,
            $imageSanitizer
        );
    }

    public function predict(
        Request $request,
        ImageSanitizer $imageSanitizer
    ) {
        $evaluationMode = (bool) $request->attributes->get(
            'evaluation_mode',
            false
        );

        // 1. Normalize the optional research case ID before validation.
        //    Ordinary clinical screening leaves this value null.
        $studyCaseId = $request->input('study_case_id');

        if (is_string($studyCaseId)) {
            $studyCaseId = strtoupper(trim($studyCaseId));
            $studyCaseId = $studyCaseId !== ''
                ? $studyCaseId
                : null;
        } else {
            $studyCaseId = null;
        }

        $request->merge([
            'study_case_id' => $studyCaseId,
        ]);

        // Validate the upload before anything else touches it.
        // Formal evaluation IDs are limited to the frozen 20-case set.
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png|max:10240', // 10MB max
            'study_case_id' => [
                $evaluationMode ? 'required' : 'nullable',
                'string',
                'max:64',
                'regex:/^RETINA-EVAL-(00[1-9]|01[0-9]|020)$/',
            ],
        ]);

        $file = $request->file('file');
        $user = $request->user();

        // During the formal evaluation, each clinician may record only one
        // successful prediction for each frozen study case. Failed/rejected
        // attempts remain retryable because they have no Prediction record.
        if ($studyCaseId !== null) {
            $alreadyCompleted = Image::query()
                ->where('user_id', $user->id)
                ->where('study_case_id', $studyCaseId)
                ->whereHas('prediction')
                ->exists();

            if ($alreadyCompleted) {
                return response()->json([
                    'detail' => 'This study case has already been completed by this clinician.',
                ], 409);
            }
        }

        // 2. Read the temporary upload, sanitize it entirely in memory,
        //    and immediately discard our reference to the original bytes.
        //    Only the newly encoded PNG may reach persistent storage or
        //    the model service.
        $sourceBytes = file_get_contents($file->getRealPath());

        if (! is_string($sourceBytes) || $sourceBytes === '') {
            throw new \RuntimeException(
                'Uploaded image could not be read safely.'
            );
        }

        try {
            $fileContents = $imageSanitizer->sanitizeToPng($sourceBytes);
        } finally {
            unset($sourceBytes);
        }

        // 3. RETINA-owned filename only. Never preserve the client's
        //    filename or extension.
        $anonymizedFilename = 'anonymousimage_'.Str::random(12).'.png';
        $storagePath = 'uploads/'.$user->id.'/'.$anonymizedFilename;

        // 3. Upload to Supabase Storage
        Storage::disk('s3')->put(
            $storagePath,
            $fileContents,
            ['ContentType' => 'image/png']
        );

        // 4. Record the image
        $image = Image::create([
            'user_id' => $user->id,
            'study_case_id' => $studyCaseId,
            'storage_path' => $storagePath,
            'anonymized_filename' => $anonymizedFilename,
            'validation_status' => 'valid',
        ]);

        AuditLog::record('uploaded_image', $image->id, 'image');

        // 5. Call the model service. Never reached by the browser directly.
        //    45s leaves room for Guzzle to time out cleanly before PHP's own
        //    max_execution_time turns it into an unhandled fatal.
        try {
            $response = Http::timeout(45)->attach(
                'file',
                $fileContents,
                $anonymizedFilename,
                ['Content-Type' => 'image/png']
            )->post(config('services.fastapi.url').'/predict');
        } catch (\Throwable $e) {
            Log::error('Model service unreachable', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);

            $image->update([
                'validation_status' => 'error',
            ]);

            return response()->json([
                'detail' => 'Model server unreachable. Please try again.',
            ], 503);
        }

        if (! $response->successful()) {
            // Only a deliberate rejection means "not a fundus image".
            // Any other status is a server fault and must NOT be recorded as
            // an image rejection -- that would corrupt the research dataset.
            $isRejection = $response->status() === 422;

            Log::warning('Model service returned an error', [
                'image_id' => $image->id,
                'status' => $response->status(),
            ]);

            $image->update([
                'validation_status' => $isRejection
                    ? 'rejected_not_fundus'
                    : 'error',
            ]);

            return response()->json([
                'detail' => $response->json('detail')
                    ?? 'Model server error.',
            ], $response->status());
        }

        $data = $response->json();

        // Honour the model's own fundus check. FastAPI returns this flag on a
        // 200 response, so without this the app would grade a non-fundus image
        // and store it as a real clinical prediction.
        //
        // Deliberately fails closed: a missing or null flag is treated as a
        // rejection, not an approval. If the model service ever stops sending
        // it, uploads break loudly rather than silently grading junk.
        if (($data['is_valid_fundus_image'] ?? null) !== true) {
            $image->update([
                'validation_status' => 'rejected_not_fundus',
            ]);

            AuditLog::record(
                'rejected_not_fundus',
                $image->id,
                'image'
            );

            return response()->json([
                'detail' => 'This does not appear to be a color fundus photograph. '
                    .'No prediction was recorded.',
                'signature_score' => $data['fundus_signature_score']
                    ?? null,
            ], 422);
        }

        // 6. Refuse to store an incomplete result.
        //    Defaulting a missing grade to "No DR" or a missing flag to
        //    "no referral" would fail toward the most reassuring answer,
        //    which is the wrong direction for a screening tool.
        $label = $data['predicted_label'] ?? null;

        $isComplete = isset(self::STAGE_INDEX[$label])
            && isset($data['confidence'])
            && isset($data['referable'])
            && isset($data['referable_probability'])
            && ! empty($data['class_probabilities']);

        if (! $isComplete) {
            Log::error('Model returned an incomplete result', [
                'image_id' => $image->id,
                'keys' => array_keys($data ?? []),
            ]);

            $image->update([
                'validation_status' => 'error',
            ]);

            return response()->json([
                'detail' => 'Model returned an incomplete result. No prediction was saved.',
            ], 502);
        }

        $prediction = Prediction::create([
            'image_id' => $image->id,
            'predicted_class' => self::STAGE_INDEX[$label],
            'confidence_score' => $data['confidence'],
            'probabilities' => $data['class_probabilities'],
            'referral_flag' => $data['referable'],
            'referable_probability' => $data['referable_probability'],
            'flagged_for_review' => $data['flagged_for_review'] ?? false,
            'atypical_fundus_image' => $data['atypical_fundus_image'] ?? false,
            'fundus_signature_score' => $data['fundus_signature_score'] ?? null,

            'gradcam_path' => null,
            'model_version' => 'efficientnet-b4-512-coral',
        ]);

        AuditLog::record(
            'prediction_created',
            $prediction->id,
            'prediction'
        );

        // 7. Same shape the frontend already expects, plus our DB prediction ID
        $data['prediction_id'] = $prediction->id;

        return response()->json($data);
    }

    public function correct(
        Request $request,
        Prediction $prediction
    ) {
        // A doctor may only correct a prediction on an image they can see.
        // Reuses scopeVisibleTo so this stays the single access-control layer.
        $canAccess = Image::whereKey($prediction->image_id)
            ->visibleTo($request->user())
            ->exists();

        if (! $canAccess) {
            AuditLog::record(
                'denied_correction',
                $prediction->id,
                'prediction'
            );

            return response()->json([
                'detail' => 'You do not have access to this prediction.',
            ], 403);
        }

        $request->validate([
            'corrected_class' => 'required|integer|min:0|max:4',
            'note' => 'nullable|string|max:2000',
        ]);

        $correction = Correction::updateOrCreate(
            [
                'prediction_id' => $prediction->id,
            ],
            [
                'corrected_by' => $request->user()->id,
                'corrected_class' => $request->corrected_class,
                'note' => $request->note,
            ]
        );

        AuditLog::record(
            'corrected_prediction',
            $correction->id,
            'correction'
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function history(
        Request $request
    ) {
        $images = Image::with('prediction.correction')
            ->visibleTo($request->user())
            ->latest()
            ->paginate(20);

        return view(
            'history',
            [
                'images' => $images,
            ]
        );
    }

    public function show(
        Request $request,
        Prediction $prediction
    ) {
        $user = $request->user();

        $image = Image::whereKey($prediction->image_id)
            ->visibleTo($user)
            ->with('user')
            ->first();

        if (! $image) {
            AuditLog::record(
                'denied_view',
                $prediction->id,
                'prediction'
            );

            abort(403);
        }

        $prediction->load(
            'correction.correctedBy'
        );

        AuditLog::record(
            'viewed_prediction',
            $prediction->id,
            'prediction'
        );

        return view(
            'prediction-detail',
            [
                'prediction' => $prediction,
                'image' => $image,
                'isAdmin' => $user->hasRole('admin'),
            ]
        );
    }

    public function imageFile(
        Request $request,
        Image $image
    ) {
        abort_unless(
            Image::whereKey($image->id)
                ->visibleTo($request->user())
                ->exists(),
            403
        );

        abort_if(
            $image->retention_purged_at !== null
                || $image->storage_path === null,
            404
        );

        $disk = Storage::disk('s3');

        abort_unless(
            $disk->exists($image->storage_path),
            404
        );

        return response(
            $disk->get($image->storage_path),
            200,
            [
                'Content-Type' => $disk->mimeType(
                    $image->storage_path
                ) ?: 'image/jpeg',

                'Cache-Control' => 'private, max-age=300',
            ]
        );
    }

    public function welcome()
    {
        return view('welcome', [
            'evaluationMode' => false,
        ]);
    }

    public function evaluation()
    {
        return view('welcome', [
            'evaluationMode' => true,
        ]);
    }
}