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

function modelContractPng(): string
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

function modelContractValidBody(array $overrides = []): array
{
    return array_merge([
        'is_valid_fundus_image' => true,
        'predicted_label' => 'Moderate NPDR',
        'confidence' => 0.72,
        'referable' => true,
        'referable_probability' => 0.81,
        'class_probabilities' => [
            ['label' => 'No DR', 'probability' => 0.05],
            ['label' => 'Mild NPDR', 'probability' => 0.08],
            ['label' => 'Moderate NPDR', 'probability' => 0.72],
            ['label' => 'Severe NPDR', 'probability' => 0.10],
            ['label' => 'PDR', 'probability' => 0.05],
        ],
        'flagged_for_review' => false,
        'atypical_fundus_image' => false,
        'fundus_signature_score' => 0.93,
    ], $overrides);
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
    ]);
});

function modelContractUpload($test)
{
    return $test->actingAs($test->doctor)->post(
        '/predict',
        ['file' => UploadedFile::fake()->createWithContent('upload.png', modelContractPng())],
        ['Accept' => 'application/json']
    );
}

test('a deliberate 422 rejection is recorded as not fundus with no prediction', function () {
    Http::fake([
        'https://retina-model.test/predict' => Http::response([
            'detail' => 'Not a fundus photograph.',
        ], 422),
    ]);

    modelContractUpload($this)->assertStatus(422);

    expect(Image::sole()->validation_status)->toBe('rejected_not_fundus')
        ->and(Prediction::count())->toBe(0);
});

test('an explicit false fundus decision on a 200 response is a deliberate rejection', function () {
    Http::fake([
        'https://retina-model.test/predict' => Http::response(
            modelContractValidBody(['is_valid_fundus_image' => false]),
            200
        ),
    ]);

    modelContractUpload($this)->assertStatus(422);

    expect(Image::sole()->validation_status)->toBe('rejected_not_fundus')
        ->and(Prediction::count())->toBe(0);
});

test('a malformed 200 response is a model-service error, never a rejection', function ($body) {
    Http::fake([
        'https://retina-model.test/predict' => Http::response($body, 200),
    ]);

    modelContractUpload($this)
        ->assertStatus(502)
        ->assertJson([
            'detail' => 'The model service returned an unusable response. No prediction was recorded.',
        ]);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
})->with([
    'missing fundus flag' => [fn () => Arr::except(modelContractValidBody(), 'is_valid_fundus_image')],
    'null fundus flag' => [fn () => modelContractValidBody(['is_valid_fundus_image' => null])],
    'non-boolean fundus flag' => [fn () => modelContractValidBody(['is_valid_fundus_image' => 'true'])],
    'invalid json body' => ['<html>Upstream proxy error</html>'],
    'empty body' => [''],
    'json scalar body' => ['"ok"'],
]);

test('an incomplete prediction payload is an error with no fabricated result', function ($overrides) {
    $body = modelContractValidBody();

    foreach ($overrides as $key => $value) {
        if ($value === '__remove__') {
            unset($body[$key]);
        } else {
            $body[$key] = $value;
        }
    }

    Http::fake([
        'https://retina-model.test/predict' => Http::response($body, 200),
    ]);

    modelContractUpload($this)->assertStatus(502);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
})->with([
    'missing predicted label' => [['predicted_label' => '__remove__']],
    'unknown predicted label' => [['predicted_label' => 'Stage 9']],
    'non-string predicted label' => [['predicted_label' => ['No DR']]],
    'missing confidence' => [['confidence' => '__remove__']],
    'missing referral decision' => [['referable' => '__remove__']],
    'missing referable probability' => [['referable_probability' => '__remove__']],
    'empty class probabilities' => [['class_probabilities' => []]],
]);

test('a model server failure is recorded as an error, not a rejection', function () {
    Http::fake([
        'https://retina-model.test/predict' => Http::response(['detail' => 'boom'], 500),
    ]);

    modelContractUpload($this)->assertStatus(500);

    expect(Image::sole()->validation_status)->toBe('error')
        ->and(Prediction::count())->toBe(0);
});

test('a complete valid 200 response creates the prediction normally', function () {
    Http::fake([
        'https://retina-model.test/predict' => Http::response(modelContractValidBody(), 200),
    ]);

    modelContractUpload($this)
        ->assertOk()
        ->assertJson([
            'predicted_label' => 'Moderate NPDR',
            'referable' => true,
        ]);

    $prediction = Prediction::sole();

    expect(Image::sole()->validation_status)->toBe('valid')
        ->and($prediction->predicted_class)->toBe(2)
        ->and($prediction->confidence_score)->toBe(0.72)
        ->and($prediction->referral_flag)->toBeTrue();
});
