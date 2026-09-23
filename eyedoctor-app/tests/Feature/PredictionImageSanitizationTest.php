<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function retinaFeatureCaptureImage(callable $writer): string
{
    ob_start();

    try {
        $writer();
        $bytes = ob_get_contents();
    } finally {
        ob_end_clean();
    }

    if (! is_string($bytes) || $bytes === '') {
        throw new RuntimeException('Feature-test image generation failed.');
    }

    return $bytes;
}

function retinaFeatureJpegWithComment(): string
{
    $image = imagecreatetruecolor(16, 16);

    if ($image === false) {
        throw new RuntimeException('Could not create feature-test image.');
    }

    $background = imagecolorallocate($image, 25, 45, 65);
    $detail = imagecolorallocate($image, 220, 80, 35);

    imagefill($image, 0, 0, $background);
    imagefilledellipse($image, 8, 8, 7, 7, $detail);

    try {
        $jpeg = retinaFeatureCaptureImage(
            fn () => imagejpeg($image, null, 95)
        );
    } finally {
        imagedestroy($image);
    }

    $comment =
        'PatientName=Juan_Dela_Cruz;PatientID=RETINA-SECRET-789';

    $commentSegment =
        "\xFF\xFE"
        .pack('n', strlen($comment) + 2)
        .$comment;

    return substr($jpeg, 0, 2)
        .$commentSegment
        .substr($jpeg, 2);
}

test(
    'prediction stores and sends only the sanitized png',
    function () {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        Role::findOrCreate('doctor', 'web');

        $user = User::factory()->create();
        $user->assignRole('doctor');

        Storage::fake('s3');

        config([
            'services.fastapi.url' => 'https://retina-model.test',
            'services.fastapi.api_key' => 'test-retina-api-key',
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

        $source = retinaFeatureJpegWithComment();

        expect($source)
            ->toContain('Juan_Dela_Cruz')
            ->toContain('RETINA-SECRET-789');

        $upload = UploadedFile::fake()->createWithContent(
            'juan_delacruz_OD.jpg',
            $source
        );

        $response = $this
            ->actingAs($user)
            ->post(
                '/predict',
                ['file' => $upload],
                ['Accept' => 'application/json']
            );

        $response->assertOk();

        $image = Image::query()->firstOrFail();

        expect($image->anonymized_filename)
            ->toMatch('/^anonymousimage_[A-Za-z0-9]{12}\.png$/');

        expect($image->storage_path)
            ->toEndWith('.png')
            ->not->toContain('juan_delacruz');

        Storage::disk('s3')->assertExists(
            $image->storage_path
        );

        $stored = Storage::disk('s3')->get(
            $image->storage_path
        );

        expect(substr($stored, 0, 8))
            ->toBe("\x89PNG\r\n\x1a\n");

        expect($stored)
            ->not->toContain('Juan_Dela_Cruz')
            ->not->toContain('RETINA-SECRET-789');

        Http::assertSent(
            function (ClientRequest $request) use (
                $image,
                $stored
            ): bool {
                $body = $request->body();

                return
                    $request->url()
                        === 'https://retina-model.test/predict'
                    && $request->hasHeader(
                        'X-API-Key',
                        'test-retina-api-key'
                    )
                    && str_contains(
                        $body,
                        $image->anonymized_filename
                    )
                    && str_contains(
                        $body,
                        'Content-Type: image/png'
                    )
                    && str_contains(
                        $body,
                        $stored
                    )
                    && ! str_contains(
                        $body,
                        'juan_delacruz_OD.jpg'
                    )
                    && ! str_contains(
                        $body,
                        'RETINA-SECRET-789'
                    );
            }
        );
    }
);