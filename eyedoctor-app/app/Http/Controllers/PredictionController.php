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

    /**
     * Master 11 (advisory modality check) fields. They arrive together or
     * not at all; a partial set is a malformed response.
     */
    private const MASTER11_FIELDS = [
        'master11_score',
        'master11_state',
        'master11_accept_threshold',
        'master11_warning',
        'modality_warning',
    ];

    private const MASTER11_ACCEPT_THRESHOLD = 0.00010095704353952897;

    /**
     * Independent reasons a result is flagged for clinician review.
     * 'model_flagged' is only derived here, for an older backend that sets
     * the flag without saying why.
     */
    private const REVIEW_REASONS = [
        'modality_warning',
        'scope_warning',
        'any_dr_probability',
    ];

    private const ANY_DR_REVIEW_THRESHOLD = 0.10;

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

        $modelApiKey = config('services.fastapi.api_key');

        if (! is_string($modelApiKey) || trim($modelApiKey) === '') {
            Log::critical(
                'RETINA model API key is not configured'
            );

            return response()->json([
                'detail' => 'Model service authentication is not configured.',
            ], 503);
        }

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
            $response = Http::withHeaders([
                'X-API-Key' => $modelApiKey,
            ])->timeout(45)->attach(
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

        // A 200 response must carry an explicit boolean fundus decision.
        // Invalid JSON or a missing/null flag is a model-service protocol
        // failure, not evidence that the image is non-fundus, so it is
        // recorded as an error (never as a rejection) and fails closed:
        // no Prediction is stored and no DR result is reported.
        if (
            ! is_array($data)
            || ! is_bool($data['is_valid_fundus_image'] ?? null)
        ) {
            Log::error('Model returned a malformed response', [
                'image_id' => $image->id,
                'keys' => is_array($data) ? array_keys($data) : [],
            ]);

            $image->update([
                'validation_status' => 'error',
            ]);

            return response()->json([
                'detail' => 'The model service returned an unusable response. '
                    .'No prediction was recorded.',
            ], 502);
        }

        // Honour the model's own explicit fundus decision. Without this the
        // app would grade a non-fundus image and store it as a real clinical
        // prediction.
        if ($data['is_valid_fundus_image'] === false) {
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

        $isComplete = is_string($label)
            && isset(self::STAGE_INDEX[$label])
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

        // Master 11 never blocks a Web prediction, but a malformed modality
        // check must not be read as a supported modality.
        $master11 = $this->master11Contract($data);
        $modalityWarning = $master11 === 'valid' && $data['master11_warning'] === true;
        $reviewReasons = $this->reviewReasons($data, $modalityWarning);

        if ($master11 === 'malformed' || $reviewReasons === null) {
            Log::error('Model returned a malformed Master 11 result', [
                'image_id' => $image->id,
                'keys' => array_keys($data),
            ]);

            $image->update([
                'validation_status' => 'error',
            ]);

            return response()->json([
                'detail' => 'Model returned an incomplete result. No prediction was saved.',
            ], 502);
        }

        if ($master11 === 'absent') {
            Log::warning('Model response did not include the Master 11 modality check', [
                'image_id' => $image->id,
            ]);
        }

        // Every review reason forces clinician review; none clears another
        // reason the backend gave for review.
        $data['review_reasons'] = $reviewReasons;
        $data['flagged_for_review'] = (bool) ($data['flagged_for_review'] ?? false)
            || $reviewReasons !== [];

        $prediction = Prediction::create([
            'image_id' => $image->id,
            'predicted_class' => self::STAGE_INDEX[$label],
            'confidence_score' => $data['confidence'],
            'probabilities' => $data['class_probabilities'],
            'referral_flag' => $data['referable'],
            'referable_probability' => $data['referable_probability'],
            'flagged_for_review' => $data['flagged_for_review'],
            'atypical_fundus_image' => $data['atypical_fundus_image'] ?? false,
            'fundus_signature_score' => $data['fundus_signature_score'] ?? null,

            'master12_score' => $data['master12_score'] ?? null,
            'master12_threshold' => $data['master12_threshold'] ?? null,
            'dr_scope_passed' => $data['dr_scope_passed'] ?? null,
            'scope_warning' => $data['scope_warning'] ?? null,

            // Null when the backend predates Master 11: "not recorded",
            // never a supported modality.
            'master11_score' => $master11 === 'valid' ? $data['master11_score'] : null,
            'master11_state' => $master11 === 'valid' ? $data['master11_state'] : null,
            'modality_warning' => $master11 === 'valid' ? $modalityWarning : null,

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

    /**
     * Classify the Master 11 part of a successful model response.
     *
     * 'valid'     every field present, well-typed and self-consistent
     * 'absent'    no field present and the check is not yet required
     *             (backend deployed before or after Laravel)
     * 'malformed' anything else; never treated as a supported modality
     */
    private function master11Contract(array $data): string
    {
        $present = array_filter(
            self::MASTER11_FIELDS,
            fn (string $field) => array_key_exists($field, $data)
        );

        if ($present === []) {
            return config('services.fastapi.require_master11')
                ? 'malformed'
                : 'absent';
        }

        if (count($present) !== count(self::MASTER11_FIELDS)) {
            return 'malformed';
        }

        $isNumber = fn ($value) => (is_int($value) || is_float($value))
            && is_finite((float) $value);

        $score = $data['master11_score'];
        $accept = $data['master11_accept_threshold'];
        $state = $data['master11_state'];
        $warning = $data['master11_warning'];

        if (
            ! $isNumber($score)
            || ! $isNumber($accept)
            || (float) $accept !== self::MASTER11_ACCEPT_THRESHOLD
            || ! is_bool($warning)
            || $data['modality_warning'] !== $warning
        ) {
            return 'malformed';
        }

        // Binary Web rule; the state must be the one it gives for this score.
        $expectedState = $score >= $accept
            ? 'SUPPORTED_CFP'
            : 'MODALITY_WARNING';

        if ($state !== $expectedState || $warning !== ($state === 'MODALITY_WARNING')) {
            return 'malformed';
        }

        return 'valid';
    }

    /**
     * The independent clinician-review reasons for a successful response,
     * or null when the backend's own list is malformed or contradicts the
     * warning fields it sits beside.
     *
     * When the backend sends no list (older backend), the reasons are
     * derived from the fields it does send.
     */
    private function reviewReasons(array $data, bool $modalityWarning): ?array
    {
        $scopeWarning = ($data['scope_warning'] ?? null) === true;

        if (array_key_exists('review_reasons', $data)) {
            $reasons = $data['review_reasons'];

            if (
                ! is_array($reasons)
                || ! array_is_list($reasons)
                || array_filter($reasons, fn ($reason) => ! is_string($reason)) !== []
                || array_diff($reasons, self::REVIEW_REASONS) !== []
                || count(array_unique($reasons, SORT_REGULAR)) !== count($reasons)
                || in_array('modality_warning', $reasons, true) !== $modalityWarning
                || in_array('scope_warning', $reasons, true) !== $scopeWarning
            ) {
                return null;
            }

            return $reasons;
        }

        $anyDr = $data['any_dr_probability'] ?? null;

        $reasons = array_keys(array_filter([
            'modality_warning' => $modalityWarning,
            'scope_warning' => $scopeWarning,
            'any_dr_probability' => ($data['predicted_label'] ?? null) === 'No DR'
                && ($data['referable'] ?? null) === false
                && (is_int($anyDr) || is_float($anyDr))
                && $anyDr >= self::ANY_DR_REVIEW_THRESHOLD,
        ]));

        if ($reasons === [] && ($data['flagged_for_review'] ?? false) === true) {
            $reasons = ['model_flagged'];
        }

        return $reasons;
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
