<?php

use App\Models\Correction;
use App\Models\Image;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function archiveIntegrityUser(string $role): User
{
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);
    acceptCurrentRetinaLegalDocuments($user);

    return $user;
}

function archiveIntegrityImage(User $owner, string $filename, string $status = 'valid'): Image
{
    return Image::create([
        'user_id' => $owner->id,
        'storage_path' => 'uploads/'.$owner->id.'/'.$filename,
        'anonymized_filename' => $filename,
        'validation_status' => $status,
    ]);
}

function archiveIntegrityPrediction(Image $image, int $class = 2, bool $referral = true): Prediction
{
    return Prediction::create([
        'image_id' => $image->id,
        'predicted_class' => $class,
        'confidence_score' => 0.80,
        'probabilities' => [
            ['label' => 'Moderate NPDR', 'probability' => 0.80],
        ],
        'referral_flag' => $referral,
        'referable_probability' => $referral ? 0.80 : 0.10,
        'flagged_for_review' => false,
        'atypical_fundus_image' => false,
        'fundus_signature_score' => 0.90,
        'gradcam_path' => null,
        'model_version' => 'archive-integrity-test-model',
    ]);
}

beforeEach(function () {
    $this->owner = archiveIntegrityUser('doctor');
    $this->otherDoctor = archiveIntegrityUser('doctor');

    $this->image = archiveIntegrityImage($this->owner, 'anonymousimage_owner.png');
    $this->prediction = archiveIntegrityPrediction($this->image);
});

test('another doctor cannot view a prediction detail page by changing the id', function () {
    $this->actingAs($this->otherDoctor)
        ->get(route('predictions.show', $this->prediction))
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->get(route('predictions.show', $this->prediction))
        ->assertOk()
        ->assertSee('Model-predicted ICDR stage');
});

test('a nonexistent prediction id returns not found', function () {
    $this->actingAs($this->owner)
        ->get('/predictions/999999')
        ->assertNotFound();
});

test('another doctor cannot download a stored image by changing the id', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put($this->image->storage_path, 'sanitized-png-bytes');

    $this->actingAs($this->otherDoctor)
        ->get(route('images.file', $this->image))
        ->assertForbidden();

    $response = $this->actingAs($this->owner)
        ->get(route('images.file', $this->image))
        ->assertOk();

    expect($response->getContent())->toBe('sanitized-png-bytes')
        ->and($response->headers->get('Cache-Control'))->toContain('private');
});

test('archive pages never expose private storage paths', function () {
    $this->actingAs($this->owner)
        ->get(route('history'))
        ->assertOk()
        ->assertDontSee($this->image->storage_path);

    $this->actingAs($this->owner)
        ->get(route('predictions.show', $this->prediction))
        ->assertOk()
        ->assertDontSee($this->image->storage_path)
        ->assertSee(route('images.file', $this->image), false);
});

test('history lists only the signed-in doctor own images', function () {
    $otherImage = archiveIntegrityImage($this->otherDoctor, 'anonymousimage_other.png');
    archiveIntegrityPrediction($otherImage);

    $this->actingAs($this->owner)
        ->get(route('history'))
        ->assertOk()
        ->assertSee('anonymousimage_owner.png')
        ->assertDontSee('anonymousimage_other.png');
});

test('administrators see all doctors records in history and detail', function () {
    $admin = archiveIntegrityUser('admin');

    $otherImage = archiveIntegrityImage($this->otherDoctor, 'anonymousimage_other.png');
    $otherPrediction = archiveIntegrityPrediction($otherImage);

    $this->actingAs($admin)
        ->get(route('history'))
        ->assertOk()
        ->assertSee('anonymousimage_owner.png')
        ->assertSee('anonymousimage_other.png');

    $this->actingAs($admin)
        ->get(route('predictions.show', $otherPrediction))
        ->assertOk();
});

test('rejected and failed images in history are never shown as ICDR predictions', function () {
    Image::query()->delete();

    archiveIntegrityImage($this->owner, 'anonymousimage_rejected.png', 'rejected_not_fundus');
    archiveIntegrityImage($this->owner, 'anonymousimage_error.png', 'error');

    $this->actingAs($this->owner)
        ->get(route('history'))
        ->assertOk()
        ->assertSee('REJECTED')
        ->assertSee('No DR classification performed')
        ->assertSee('PREDICTION UNAVAILABLE')
        ->assertDontSee('confidence')
        ->assertDontSee('NO REFERRAL')
        ->assertDontSee('/predictions/', false);
});

test('administrators cannot record clinician corrections', function () {
    $admin = archiveIntegrityUser('admin');

    $this->actingAs($admin)
        ->postJson(route('predictions.correct', $this->prediction), [
            'corrected_class' => 1,
        ])
        ->assertForbidden();

    expect(Correction::count())->toBe(0);
});

test('correction class outside the ICDR scale is rejected', function () {
    $this->actingAs($this->owner)
        ->postJson(route('predictions.correct', $this->prediction), [
            'corrected_class' => 5,
        ])
        ->assertUnprocessable();

    expect(Correction::count())->toBe(0);
});

test('detail page keeps the original model result distinct from the latest correction', function () {
    $this->travelTo(now()->subDay());

    $this->actingAs($this->owner)
        ->postJson(route('predictions.correct', $this->prediction), [
            'corrected_class' => 1,
        ])
        ->assertOk();

    $this->travelBack();

    $this->actingAs($this->owner)
        ->postJson(route('predictions.correct', $this->prediction), [
            'corrected_class' => 4,
            'note' => 'Neovascularisation visible on review.',
        ])
        ->assertOk();

    $correction = Correction::sole();

    expect($correction->prediction_id)->toBe($this->prediction->id)
        ->and($correction->corrected_class)->toBe(4)
        ->and($this->prediction->fresh()->predicted_class)->toBe(2);

    $this->actingAs($this->owner)
        ->get(route('predictions.show', $this->prediction))
        ->assertOk()
        ->assertSeeInOrder([
            'Model referral result',
            'Model-predicted ICDR stage',
            'Clinician correction',
            'Neovascularisation visible on review.',
            $correction->updated_at->format('M j, Y g:i A'),
            'The original model result above is retained unchanged.',
        ]);
});
