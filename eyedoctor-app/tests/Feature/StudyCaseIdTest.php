<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function retinaStudyCasePng(): string
{
    $image = imagecreatetruecolor(8, 8);

    if ($image === false) {
        throw new RuntimeException(
            'Could not create study-case test image.'
        );
    }

    $background = imagecolorallocate(
        $image,
        30,
        60,
        90
    );

    imagefill(
        $image,
        0,
        0,
        $background
    );

    ob_start();

    try {
        imagepng($image);
        $bytes = ob_get_contents();
    } finally {
        ob_end_clean();
        imagedestroy($image);
    }

    if (! is_string($bytes) || $bytes === '') {
        throw new RuntimeException(
            'Could not encode study-case test image.'
        );
    }

    return $bytes;
}

function retinaStudyCaseDoctor(): User
{
    app(PermissionRegistrar::class)
        ->forgetCachedPermissions();

    Role::findOrCreate(
        'doctor',
        'web'
    );

    $user = User::factory()->create();

    $user->assignRole('doctor');

    return $user;
}

function retinaStudyCaseFakeModel(): void
{
    config([
        'services.fastapi.url' => 'https://retina-model.test',
    ]);

    Http::fake([
        'https://retina-model.test/predict' => Http::response([
            'is_valid_fundus_image' => true,
            'predicted_label' => 'No DR',
            'confidence' => 0.99,
            'referable' => false,
            'referable_probability' => 0.01,
            'class_probabilities' => [
                0.99,
                0.003,
                0.003,
                0.002,
                0.002,
            ],
            'flagged_for_review' => false,
            'atypical_fundus_image' => false,
            'fundus_signature_score' => 0.95,
        ], 200),
    ]);
}

function retinaStudyCasePayload(
    ?string $studyCaseId = null
): array {
    $payload = [
        'file' => UploadedFile::fake()
            ->createWithContent(
                'fundus.png',
                retinaStudyCasePng()
            ),
    ];

    if ($studyCaseId !== null) {
        $payload['study_case_id'] =
            $studyCaseId;
    }

    return $payload;
}

test(
    'study case id is optional for ordinary screening',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $response = $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload(),
                ['Accept' => 'application/json']
            );

        $response->assertOk();

        expect(
            Image::query()
                ->firstOrFail()
                ->study_case_id
        )->toBeNull();
    }
);

test(
    'study case id is trimmed uppercased and persisted',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $response = $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    '  retina-eval-001  '
                ),
                ['Accept' => 'application/json']
            );

        $response->assertOk();

        expect(
            Image::query()
                ->firstOrFail()
                ->study_case_id
        )->toBe('RETINA-EVAL-001');
    }
);

test(
    'blank study case id becomes null',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $response = $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload('     '),
                ['Accept' => 'application/json']
            );

        $response->assertOk();

        expect(
            Image::query()
                ->firstOrFail()
                ->study_case_id
        )->toBeNull();
    }
);

test(
    'same study case may be submitted by multiple clinicians',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $doctorOne = retinaStudyCaseDoctor();
        $doctorTwo = retinaStudyCaseDoctor();

        $this
            ->actingAs($doctorOne)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-001'
                ),
                ['Accept' => 'application/json']
            )
            ->assertOk();

        $this
            ->actingAs($doctorTwo)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-001'
                ),
                ['Accept' => 'application/json']
            )
            ->assertOk();

        expect(
            Image::query()
                ->where(
                    'study_case_id',
                    'RETINA-EVAL-001'
                )
                ->count()
        )->toBe(2);
    }
);

test(
    'patient-like identifiers are rejected as study case ids',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'PATIENT-123'
                ),
                ['Accept' => 'application/json']
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'study_case_id'
            );

        expect(
            Image::query()->count()
        )->toBe(0);
    }
);

test(
    'study case ids outside the frozen evaluation set are rejected',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-021'
                ),
                ['Accept' => 'application/json']
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'study_case_id'
            );

        expect(
            Image::query()->count()
        )->toBe(0);
    }
);

test(
    'study case id longer than sixty four characters is rejected',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $user = retinaStudyCaseDoctor();

        $this
            ->actingAs($user)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    str_repeat('A', 65)
                ),
                ['Accept' => 'application/json']
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'study_case_id'
            );

        expect(
            Image::query()->count()
        )->toBe(0);
    }
);
test(
    'same clinician cannot submit the same completed study case twice',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $doctor = retinaStudyCaseDoctor();

        $this
            ->actingAs($doctor)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-001'
                ),
                ['Accept' => 'application/json']
            )
            ->assertOk();

        $this
            ->actingAs($doctor)
            ->post(
                '/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-001'
                ),
                ['Accept' => 'application/json']
            )
            ->assertStatus(409)
            ->assertJson([
                'detail' =>
                    'This study case has already been completed by this clinician.',
            ]);

        expect(
            Image::query()
                ->where(
                    'study_case_id',
                    'RETINA-EVAL-001'
                )
                ->count()
        )->toBe(1);
    }
);
test(
    'formal evaluation requires a study case id',
    function () {
        Storage::fake('s3');

        $doctor = retinaStudyCaseDoctor();

        $this
            ->actingAs($doctor)
            ->post(
                '/evaluation/predict',
                retinaStudyCasePayload(),
                ['Accept' => 'application/json']
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors(
                'study_case_id'
            );

        expect(
            Image::query()->count()
        )->toBe(0);
    }
);

test(
    'formal evaluation accepts and persists a valid study case id',
    function () {
        Storage::fake('s3');
        retinaStudyCaseFakeModel();

        $doctor = retinaStudyCaseDoctor();

        $this
            ->actingAs($doctor)
            ->post(
                '/evaluation/predict',
                retinaStudyCasePayload(
                    'RETINA-EVAL-002'
                ),
                ['Accept' => 'application/json']
            )
            ->assertOk();

        expect(
            Image::query()
                ->where(
                    'study_case_id',
                    'RETINA-EVAL-002'
                )
                ->whereHas('prediction')
                ->exists()
        )->toBeTrue();
    }
);