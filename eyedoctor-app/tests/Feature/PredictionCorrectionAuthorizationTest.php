<?php

use App\Models\Image;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('doctor', 'web');

    $this->owner = User::factory()->create();
    $this->owner->assignRole('doctor');

    $this->otherDoctor = User::factory()->create();
    $this->otherDoctor->assignRole('doctor');

    $this->image = Image::create([
        'user_id' => $this->owner->id,
        'storage_path' => 'uploads/test/authorization-test.png',
        'anonymized_filename' => 'authorization-test.png',
        'validation_status' => 'valid',
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
        'model_version' => 'authorization-test-model',
    ]);
});

test('another doctor cannot correct a prediction they do not own', function () {
    $response = $this
        ->actingAs($this->otherDoctor)
        ->postJson(
            route(
                'predictions.correct',
                $this->prediction
            ),
            [
                'corrected_class' => 1,
                'note' => 'Unauthorized correction attempt.',
            ]
        );

    $response
        ->assertForbidden()
        ->assertJson([
            'detail' => 'You do not have access to this prediction.',
        ]);

    $this->assertDatabaseMissing(
        'corrections',
        [
            'prediction_id' => $this->prediction->id,
        ]
    );
});

test('doctor can correct their own prediction', function () {
    $response = $this
        ->actingAs($this->owner)
        ->postJson(
            route(
                'predictions.correct',
                $this->prediction
            ),
            [
                'corrected_class' => 1,
                'note' => 'Clinician reviewed as Mild NPDR.',
            ]
        );

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas(
        'corrections',
        [
            'prediction_id' => $this->prediction->id,
            'corrected_by' => $this->owner->id,
            'corrected_class' => 1,
        ]
    );
});
