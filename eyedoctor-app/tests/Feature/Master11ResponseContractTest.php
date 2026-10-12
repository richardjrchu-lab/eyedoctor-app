<?php

use App\Models\Image;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

const MASTER11_TEST_ACCEPT = 0.00010095704353952897;

const MASTER11_TEST_FIELDS = [
    'master11_score', 'master11_state', 'master11_accept_threshold',
    'master11_warning', 'modality_warning',
    'modality_warning_title', 'modality_warning_message',
];

function master11Png(): string
{
    $image = imagecreatetruecolor(8, 8);
    imagefill($image, 0, 0, imagecolorallocate($image, 120, 40, 20));

    ob_start();

    try {
        imagepng($image);

        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
        imagedestroy($image);
    }
}

/**
 * A complete backend success body, shaped like the Master 11 backend's.
 * Defaults: supported modality, Master 12 pass, referable Moderate NPDR.
 */
function master11Body(float $score = 0.9999, array $overrides = []): array
{
    $warning = $score < MASTER11_TEST_ACCEPT;

    $body = array_merge([
        'is_valid_fundus_image' => true,
        'predicted_label' => 'Moderate NPDR',
        'predicted_class_index' => 2,
        'confidence' => 0.72,
        'referable' => true,
        'referable_probability' => 0.81,
        'referral_threshold' => 0.49,
        'any_dr_probability' => 0.95,
        'class_probabilities' => [
            ['label' => 'No DR', 'probability' => 0.05],
            ['label' => 'Mild NPDR', 'probability' => 0.08],
            ['label' => 'Moderate NPDR', 'probability' => 0.72],
            ['label' => 'Severe NPDR', 'probability' => 0.10],
            ['label' => 'PDR', 'probability' => 0.05],
        ],
        'atypical_fundus_image' => false,
        'fundus_signature_score' => 0.93,
        'master11_score' => $score,
        'master11_state' => $warning ? 'MODALITY_WARNING' : 'SUPPORTED_CFP',
        'master11_accept_threshold' => MASTER11_TEST_ACCEPT,
        'master11_warning' => $warning,
        'modality_warning' => $warning,
        'modality_warning_title' => $warning ? 'Imaging Modality Warning' : null,
        'modality_warning_message' => $warning ? 'Modality warning text.' : null,
        'master12_score' => 0.91,
        'master12_threshold' => 0.475,
        'dr_scope_passed' => true,
        'scope_warning' => false,
        'review_reasons' => $warning ? ['modality_warning'] : [],
    ], $overrides);

    $body['flagged_for_review'] ??= $body['review_reasons'] !== [];

    return $body;
}

/**
 * Overrides for a No DR, non-referable result that meets the any-DR
 * review condition.
 */
function master11AnyDrReview(): array
{
    return [
        'predicted_label' => 'No DR',
        'predicted_class_index' => 0,
        'confidence' => 0.60,
        'referable' => false,
        'referable_probability' => 0.12,
        'any_dr_probability' => 0.40,
    ];
}

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::findOrCreate('doctor', 'web');

    $this->doctor = User::factory()->create();
    $this->doctor->assignRole('doctor');
    acceptCurrentRetinaLegalDocuments($this->doctor);

    Storage::fake('s3');

    config([
        'services.fastapi.url' => 'https://retina-model.test',
        'services.fastapi.api_key' => 'test-retina-api-key',
        'services.fastapi.require_master11' => false,
    ]);
});

function master11Upload($test, array|string $body, int $status = 200)
{
    Http::fake([
        'https://retina-model.test/predict' => Http::response($body, $status),
    ]);

    return $test->actingAs($test->doctor)->post(
        '/predict',
        ['file' => UploadedFile::fake()->createWithContent('upload.png', master11Png())],
        ['Accept' => 'application/json']
    );
}

function master11ExpectReferableStagePreserved(): Prediction
{
    $prediction = Prediction::sole();

    expect(Image::sole()->validation_status)->toBe('valid')
        ->and($prediction->predicted_class)->toBe(2)
        ->and($prediction->confidence_score)->toBe(0.72)
        ->and($prediction->referral_flag)->toBeTrue()
        ->and($prediction->referable_probability)->toBe(0.81)
        ->and($prediction->probabilities)->toHaveCount(5);

    return $prediction;
}

// ---------------------------------------------------------------------------
// Binary Master 11 rule
// ---------------------------------------------------------------------------

test('a normal supported score creates the prediction with no modality warning', function () {
    master11Upload($this, master11Body(0.9999))
        ->assertOk()
        ->assertJson([
            'master11_state' => 'SUPPORTED_CFP',
            'modality_warning' => false,
            'flagged_for_review' => false,
            'review_reasons' => [],
            'prediction_id' => Prediction::sole()->id,
        ]);

    $prediction = master11ExpectReferableStagePreserved();

    expect($prediction->flagged_for_review)->toBeFalse()
        ->and($prediction->master11_state)->toBe('SUPPORTED_CFP')
        ->and($prediction->modality_warning)->toBeFalse();
});

test('the exact accept boundary is a supported modality', function () {
    master11Upload($this, master11Body(MASTER11_TEST_ACCEPT))
        ->assertOk()
        ->assertJson(['master11_state' => 'SUPPORTED_CFP', 'modality_warning' => false]);
});

test('a score just below the accept boundary is a modality warning', function () {
    $justBelow = MASTER11_TEST_ACCEPT * (1 - 1e-15);

    expect($justBelow)->toBeLessThan(MASTER11_TEST_ACCEPT);

    master11Upload($this, master11Body($justBelow))
        ->assertOk()
        ->assertJson(['master11_state' => 'MODALITY_WARNING', 'modality_warning' => true]);
});

test('a zero score is a modality warning that still creates the prediction', function () {
    master11Upload($this, master11Body(0.0))
        ->assertOk()
        ->assertJson([
            'predicted_label' => 'Moderate NPDR',
            'referable' => true,
            'master11_state' => 'MODALITY_WARNING',
            'master11_warning' => true,
            'modality_warning' => true,
            'flagged_for_review' => true,
            'review_reasons' => ['modality_warning'],
        ]);

    $prediction = master11ExpectReferableStagePreserved();

    expect($prediction->flagged_for_review)->toBeTrue()
        ->and($prediction->master11_state)->toBe('MODALITY_WARNING')
        ->and($prediction->master11_score)->toBe(0.0)
        ->and($prediction->modality_warning)->toBeTrue()
        ->and(Image::sole()->validation_status)->toBe('valid');
});

test('a modality warning forces review even if the backend review flag is false', function () {
    master11Upload($this, master11Body(0.0, ['flagged_for_review' => false]))
        ->assertOk()
        ->assertJson(['flagged_for_review' => true]);

    expect(Prediction::sole()->flagged_for_review)->toBeTrue();
});

test('a modality warning with a referable B4 output keeps the referral', function () {
    master11Upload($this, master11Body(0.0))
        ->assertOk()
        ->assertJson([
            'referable' => true,
            'referral_threshold' => 0.49,
            'review_reasons' => ['modality_warning'],
        ]);

    expect(master11ExpectReferableStagePreserved()->referral_flag)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Independent review reasons
// ---------------------------------------------------------------------------

test('modality and Master 12 warnings are preserved together', function () {
    master11Upload($this, master11Body(0.0, [
        'master12_score' => 0.10,
        'dr_scope_passed' => false,
        'scope_warning' => true,
        'scope_warning_message' => 'Scope warning text.',
        'review_reasons' => ['modality_warning', 'scope_warning'],
    ]))
        ->assertOk()
        ->assertJson([
            'modality_warning' => true,
            'modality_warning_message' => 'Modality warning text.',
            'scope_warning' => true,
            'scope_warning_message' => 'Scope warning text.',
            'review_reasons' => ['modality_warning', 'scope_warning'],
            'flagged_for_review' => true,
        ]);

    $prediction = master11ExpectReferableStagePreserved();

    expect($prediction->scope_warning)->toBeTrue()
        ->and($prediction->modality_warning)->toBeTrue()
        ->and($prediction->master12_score)->toBe(0.10);
});

test('a modality warning with the non-referable any-DR condition keeps both reasons', function () {
    master11Upload($this, master11Body(0.0, master11AnyDrReview() + [
        'review_reasons' => ['modality_warning', 'any_dr_probability'],
    ]))
        ->assertOk()
        ->assertJson([
            'predicted_label' => 'No DR',
            'referable' => false,
            'review_reasons' => ['modality_warning', 'any_dr_probability'],
            'flagged_for_review' => true,
        ]);

    expect(Prediction::sole()->referral_flag)->toBeFalse();
});

test('all three review reasons are preserved together', function () {
    master11Upload($this, master11Body(0.0, master11AnyDrReview() + [
        'scope_warning' => true,
        'dr_scope_passed' => false,
        'master12_score' => 0.10,
        'review_reasons' => ['modality_warning', 'scope_warning', 'any_dr_probability'],
    ]))
        ->assertOk()
        ->assertJson([
            'review_reasons' => ['modality_warning', 'scope_warning', 'any_dr_probability'],
            'flagged_for_review' => true,
        ]);
});

test('review reasons are derived when an older backend sends no list', function ($overrides, $expected) {
    $body = Arr::except(master11Body(0.9999, $overrides), 'review_reasons');

    master11Upload($this, $body)
        ->assertOk()
        ->assertJson([
            'review_reasons' => $expected,
            'flagged_for_review' => $expected !== [],
        ]);
})->with([
    'nothing to review' => [[], []],
    'scope warning' => [['scope_warning' => true], ['scope_warning']],
    'any-DR condition' => [master11AnyDrReview(), ['any_dr_probability']],
    'flag without a known reason' => [['flagged_for_review' => true], ['model_flagged']],
]);

test('malformed review reasons fail closed', function ($reasons) {
    master11Upload($this, master11Body(0.0, ['review_reasons' => $reasons]))
        ->assertStatus(502);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
})->with([
    'not a list' => [['modality_warning' => true]],
    'string' => ['modality_warning'],
    'unknown reason' => [['modality_warning', 'gut_feeling']],
    'non-string entry' => [['modality_warning', ['scope_warning']]],
    'duplicate reason' => [['modality_warning', 'modality_warning']],
    'missing modality reason' => [[]],
    'scope reason without scope warning' => [['modality_warning', 'scope_warning']],
]);

// ---------------------------------------------------------------------------
// Malformed Master 11 fields and staged rollout
// ---------------------------------------------------------------------------

test('a malformed Master 11 result fails closed and is never treated as supported', function ($body) {
    master11Upload($this, $body)
        ->assertStatus(502)
        ->assertJson([
            'detail' => 'Model returned an incomplete result. No prediction was saved.',
        ]);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
})->with([
    'partial field set' => [fn () => Arr::except(master11Body(), 'master11_state')],
    'missing score' => [fn () => Arr::except(master11Body(0.0), 'master11_score')],
    'unknown state' => [fn () => master11Body(overrides: ['master11_state' => 'SUPPORTED'])],
    'historical three-state label' => [fn () => master11Body(0.0, ['master11_state' => 'BORDERLINE'])],
    'null state' => [fn () => master11Body(overrides: ['master11_state' => null])],
    'string score' => [fn () => master11Body(overrides: ['master11_score' => '0.9'])],
    'null score' => [fn () => master11Body(overrides: ['master11_score' => null])],
    'string warning' => [fn () => master11Body(0.0, ['master11_warning' => 'true', 'modality_warning' => 'true'])],
    'warning false on a low score' => [fn () => master11Body(0.0, ['master11_state' => 'MODALITY_WARNING', 'master11_warning' => false, 'modality_warning' => false, 'review_reasons' => []])],
    'warning true on a supported score' => [fn () => master11Body(overrides: ['master11_warning' => true, 'modality_warning' => true])],
    'modality warning disagrees' => [fn () => master11Body(0.0, ['modality_warning' => false])],
    'supported state for a low score' => [fn () => master11Body(0.0, ['master11_state' => 'SUPPORTED_CFP'])],
    'wrong accept threshold' => [fn () => master11Body(overrides: ['master11_accept_threshold' => 0.5])],
]);

test('a response without any Master 11 field is accepted during staged rollout', function () {
    $legacy = Arr::except(master11Body(), [...MASTER11_TEST_FIELDS, 'review_reasons']);

    $response = master11Upload($this, $legacy)->assertOk();

    // Absence is passed through as absence, never as a supported modality.
    expect($response->json())->not->toHaveKey('master11_state')
        ->and($response->json('modality_warning'))->toBeNull();

    $prediction = master11ExpectReferableStagePreserved();

    expect($prediction->master11_state)->toBeNull()
        ->and($prediction->master11_score)->toBeNull()
        ->and($prediction->modality_warning)->toBeNull();
});

test('a response without Master 11 fails closed once the check is required', function () {
    config(['services.fastapi.require_master11' => true]);

    master11Upload($this, Arr::except(master11Body(), MASTER11_TEST_FIELDS))
        ->assertStatus(502);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
});

test('a Master 11 backend failure is an error, never a Gate 1 rejection', function () {
    master11Upload($this, [
        'detail' => 'MASTER11_OUTPUT_INVALID: modality check could not be completed',
    ], 500)->assertStatus(500);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
});

test('a Gate 1 rejection is unchanged by Master 11', function () {
    master11Upload($this, ['detail' => 'NON_FUNDUS: not a fundus photograph.'], 422)
        ->assertStatus(422);

    expect(Image::sole()->validation_status)->toBe('rejected_not_fundus')
        ->and(Prediction::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Archive persistence
// ---------------------------------------------------------------------------

test('an archived prediction shows its saved modality warning', function () {
    master11Upload($this, master11Body(0.00001234))->assertOk();

    $this->actingAs($this->doctor)
        ->get(route('predictions.show', Prediction::sole()))
        ->assertOk()
        ->assertSee('IMAGING MODALITY WARNING')
        ->assertSee('clinician review is required.')
        ->assertSee('1.234e-5')
        ->assertDontSee('Modality check: Not recorded')
        ->assertSee('Model-predicted ICDR stage');
});

test('an archived supported prediction shows no modality warning', function () {
    master11Upload($this, master11Body(0.9999))->assertOk();

    $this->actingAs($this->doctor)
        ->get(route('predictions.show', Prediction::sole()))
        ->assertOk()
        ->assertSee('Modality check: No modality warning')
        ->assertDontSee('IMAGING MODALITY WARNING');
});

test('a legacy archived prediction shows the modality check as not recorded', function () {
    $image = Image::create([
        'user_id' => $this->doctor->id,
        'storage_path' => 'uploads/'.$this->doctor->id.'/anonymousimage_legacy.png',
        'anonymized_filename' => 'anonymousimage_legacy.png',
        'validation_status' => 'valid',
    ]);

    $prediction = Prediction::create([
        'image_id' => $image->id,
        'predicted_class' => 2,
        'confidence_score' => 0.80,
        'probabilities' => [['label' => 'Moderate NPDR', 'probability' => 0.80]],
        'referral_flag' => true,
        'referable_probability' => 0.80,
        'flagged_for_review' => false,
        'atypical_fundus_image' => false,
        'fundus_signature_score' => 0.90,
        'model_version' => 'legacy-test-model',
    ]);

    expect($prediction->fresh()->master11_state)->toBeNull()
        ->and($prediction->fresh()->modality_warning)->toBeNull();

    $this->actingAs($this->doctor)
        ->get(route('predictions.show', $prediction))
        ->assertOk()
        ->assertSee('Modality check: Not recorded')
        ->assertDontSee('IMAGING MODALITY WARNING')
        ->assertDontSee('Modality check: No modality warning');
});
