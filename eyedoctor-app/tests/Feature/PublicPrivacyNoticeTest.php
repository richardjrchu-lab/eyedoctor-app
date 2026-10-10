<?php

use App\Models\AccessRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function publicPrivacyAccessRequest(): AccessRequest
{
    return AccessRequest::query()->create([
        'full_name' => 'Privacy Notice Test Applicant',
        'email' => 'privacy-applicant@example.test',
        'profession' => 'physician',
        'institution' => 'RETINA Test Medical Center',
        'department_position' => 'Ophthalmology',
        'proof_type' => 'institution_id',
        'proof_disk' => 'professional_verifications',
        'proof_object_key' => 'access-requests/test/'.Str::uuid().'.png',
        'proof_mime_type' => 'image/png',
        'proof_size_bytes' => 1024,
        'proof_sha256' => hash('sha256', 'privacy-applicant'),
        'proof_uploaded_at' => now(),
        'submission_count' => 1,
        'last_submitted_at' => now(),
        'privacy_consent_at' => now(),
        'privacy_notice_version' => 'test-v1',
        'appropriate_use_consent_at' => now(),
        'appropriate_use_notice_version' => 'test-v1',
    ]);
}

test('an unauthenticated applicant can read the privacy notice', function () {
    $this->get(route('public.privacy'))
        ->assertOk()
        ->assertSee('RETINA Web Privacy Notice')
        ->assertSee('14. Professional Access Requests')
        ->assertSee('verification document')
        ->assertSee('Professional verification documents are retained only')
        ->assertSee('A specific retention schedule for these documents is')
        ->assertDontSee('has not yet been set')
        ->assertSee('Version '.config('legal.privacy_version'))
        ->assertDontSee(route('legal.privacy.accept'), false)
        ->assertDontSee('Acknowledge &amp; Continue', false)
        ->assertSee(route('access-request.create'), false);
});

test('the access request form links to the public privacy notice', function () {
    $this->get(route('access-request.create'))
        ->assertOk()
        ->assertSee(route('public.privacy').'#professional-access-requests', false);
});

test('account holders still acknowledge the notice through the protected route', function () {
    $this->get(route('legal.privacy'))->assertRedirect(route('login'));

    Role::findOrCreate('doctor', 'web');

    $doctor = User::factory()->create();
    $doctor->assignRole('doctor');
    acceptCurrentRetinaLegalDocuments($doctor);

    $this->actingAs($doctor)
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee(route('legal.privacy.accept'), false)
        ->assertSee('14. Professional Access Requests');
});

test('protected doctor and admin pages remain protected from guests', function () {
    foreach (['welcome', 'screening', 'evaluation', 'history', 'mobile-app', 'profile.edit', 'admin.access-requests.index'] as $page) {
        $this->get(route($page))->assertRedirect(route('login'));
    }
});

test('applicant verification documents are never publicly accessible', function () {
    Storage::fake('professional_verifications');

    $accessRequest = publicPrivacyAccessRequest();

    Storage::disk('professional_verifications')->put($accessRequest->proof_object_key, 'proof-bytes');

    $proofUrl = route('admin.access-requests.proof', $accessRequest);

    $this->get($proofUrl)->assertRedirect(route('login'));

    Role::findOrCreate('doctor', 'web');

    $doctor = User::factory()->create();
    $doctor->assignRole('doctor');

    $this->actingAs($doctor)->get($proofUrl)->assertForbidden();

    $this->get(route('public.privacy'))
        ->assertOk()
        ->assertDontSee($accessRequest->proof_object_key)
        ->assertDontSee($accessRequest->email);
});

function publicPrivacySubmissionPayload(string $email): array
{
    $image = imagecreatetruecolor(16, 16);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 200, 200));

    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    imagedestroy($image);

    return [
        'full_name' => 'Privacy Version Test Professional',
        'email' => $email,
        'profession' => 'physician',
        'institution' => 'RETINA Test Medical Center',
        'department_position' => 'Ophthalmology',
        'license_registration_number' => null,
        'proof_type' => 'institution_id',
        'proof_document' => \Illuminate\Http\UploadedFile::fake()->createWithContent('proof.png', $png),
        'cf-turnstile-response' => 'privacy-version-test-token',
        'privacy_consent' => '1',
        'appropriate_use_consent' => '1',
    ];
}

function publicPrivacySubmit($test, string $email)
{
    $turnstile = Mockery::mock(\App\Services\TurnstileVerifier::class);
    $turnstile->shouldReceive('verify')->andReturn(true);
    app()->instance(\App\Services\TurnstileVerifier::class, $turnstile);

    Storage::fake('professional_verifications');
    \Illuminate\Support\Facades\Mail::fake();

    return $test->from(route('access-request.create'))
        ->post(route('access-request.store'), publicPrivacySubmissionPayload($email));
}

test('a new access request records the canonical Privacy Notice version', function () {
    expect(config('legal.privacy_version'))->toBe('1.1');

    publicPrivacySubmit($this, 'canonical-version@example.test')
        ->assertRedirect(route('access-request.received'));

    expect(AccessRequest::sole()->privacy_notice_version)->toBe('1.1');
});

test('applicant acknowledgements follow the configured Privacy Notice version', function () {
    config(['legal.privacy_version' => '7.3']);

    publicPrivacySubmit($this, 'configured-version@example.test')
        ->assertRedirect(route('access-request.received'));

    expect(AccessRequest::sole()->privacy_notice_version)->toBe('7.3');
});

test('access requests fail closed when the Privacy Notice version is not configured', function () {
    config(['legal.privacy_version' => '   ']);

    publicPrivacySubmit($this, 'missing-version@example.test')
        ->assertRedirect(route('access-request.create'))
        ->assertSessionHasErrors('submission');

    expect(AccessRequest::count())->toBe(0);
    expect(Storage::disk('professional_verifications')->allFiles())->toBe([]);
});

test('users who acknowledged Privacy Notice 1.0 must acknowledge version 1.1', function () {
    Role::findOrCreate('doctor', 'web');

    $doctor = User::factory()->create();
    $doctor->assignRole('doctor');

    \App\Models\LegalAcceptance::create([
        'user_id' => $doctor->id,
        'document_type' => 'eula',
        'document_version' => (string) config('legal.eula_version'),
        'accepted_at' => now()->subMonth(),
    ]);

    \App\Models\LegalAcceptance::create([
        'user_id' => $doctor->id,
        'document_type' => 'privacy',
        'document_version' => '1.0',
        'accepted_at' => now()->subMonth(),
    ]);

    $this->actingAs($doctor)
        ->get(route('screening'))
        ->assertRedirect(route('legal.privacy'));

    $this->actingAs($doctor)
        ->post(route('legal.privacy.accept'), [
            'acknowledge' => '1',
            'document_version' => '1.1',
        ])
        ->assertRedirect('/screening');

    $this->actingAs($doctor)->get(route('screening'))->assertOk();

    // The historical 1.0 acknowledgement is kept, not rewritten.
    expect(
        \App\Models\LegalAcceptance::query()
            ->where('user_id', $doctor->id)
            ->where('document_type', 'privacy')
            ->orderBy('document_version')
            ->pluck('document_version')
            ->all()
    )->toBe(['1.0', '1.1']);
});
