<?php

use App\Models\Image;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('doctor', 'web');

    $this->doctor = User::factory()->create();
    $this->doctor->assignRole('doctor');

    $this->image = Image::create([
        'user_id' => $this->doctor->id,
        'storage_path' => null,
        'anonymized_filename' => 'anonymous_retained_record.png',
        'validation_status' => 'valid',
        'retention_purged_at' => now(),
    ]);

    $this->prediction = Prediction::create([
        'image_id' => $this->image->id,
        'predicted_class' => 2,
        'confidence_score' => 0.80,
        'probabilities' => [
            [
                'label' => 'Moderate NPDR',
                'probability' => 0.80,
            ],
        ],
        'referral_flag' => true,
        'referable_probability' => 0.80,
        'flagged_for_review' => false,
        'atypical_fundus_image' => false,
        'fundus_signature_score' => 0.90,
        'gradcam_path' => null,
        'model_version' => 'retention-ui-test-model',
    ]);
});

test('purged prediction detail preserves result and replaces source image with retention notice', function () {
    $response = $this
        ->actingAs($this->doctor)
        ->get(
            route(
                'predictions.show',
                $this->prediction
            )
        );

    $response
        ->assertOk()
        ->assertSee(
            'Source retinal image removed under the one-year retention policy.'
        )
        ->assertSee(
            'Stage 2: Moderate Non-Proliferative DR'
        )
        ->assertDontSee(
            route(
                'images.file',
                $this->image
            ),
            false
        );
});

test('history preserves purged prediction record and labels source image removed', function () {
    $response = $this
        ->actingAs($this->doctor)
        ->get(route('history'));

    $response
        ->assertOk()
        ->assertSee(
            'anonymous_retained_record.png'
        )
        ->assertSee(
            'Source image removed'
        )
        ->assertSee(
            'View details'
        );
});

test('authorized purged image request returns controlled 404 without touching storage', function () {
    Storage::shouldReceive('disk')
        ->andThrow(
            new RuntimeException(
                'Storage must not be touched for a purged image.'
            )
        );

    $response = $this
        ->actingAs($this->doctor)
        ->get(
            route(
                'images.file',
                $this->image
            )
        );

    $response->assertNotFound();
});

test('another doctor cannot access a purged image source route', function () {
    $otherDoctor = User::factory()->create();
    $otherDoctor->assignRole('doctor');

    $response = $this
        ->actingAs($otherDoctor)
        ->get(
            route(
                'images.file',
                $this->image
            )
        );

    $response->assertForbidden();
});
