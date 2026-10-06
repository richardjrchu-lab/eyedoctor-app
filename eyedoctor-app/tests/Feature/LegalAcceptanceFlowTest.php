<?php

use App\Models\LegalAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function createLegalTestDoctor(): User
{
    Role::findOrCreate('doctor', 'web');

    $user = User::factory()->create();

    $user->assignRole('doctor');

    return $user;
}

test('legal pages require authentication', function () {
    $this
        ->get(route('legal.eula'))
        ->assertRedirect(route('login'));

    $this
        ->get(route('legal.privacy'))
        ->assertRedirect(route('login'));
});

test('doctor without current legal acceptance is sent to EULA', function () {
    $user = createLegalTestDoctor();

    $this
        ->actingAs($user)
        ->get(route('screening'))
        ->assertRedirect(route('legal.eula'));
});

test('current EULA can be accepted and is recorded', function () {
    $user = createLegalTestDoctor();

    $version = (string) config('legal.eula_version');

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), [
            'agree' => '1',
            'document_version' => $version,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('legal.privacy'));

    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => $version,
    ]);
});

test('repeated EULA submission does not create duplicate acceptance records', function () {
    $user = createLegalTestDoctor();

    $version = (string) config('legal.eula_version');

    $payload = [
        'agree' => '1',
        'document_version' => $version,
    ];

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), $payload);

    $firstAcceptance = LegalAcceptance::query()
        ->where('user_id', $user->id)
        ->where('document_type', 'eula')
        ->where('document_version', $version)
        ->firstOrFail();

    $originalAcceptedAt = $firstAcceptance->accepted_at;

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), $payload);

    expect(
        LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('document_type', 'eula')
            ->where('document_version', $version)
            ->count()
    )->toBe(1);

    expect(
        $firstAcceptance
            ->fresh()
            ->accepted_at
            ->equalTo($originalAcceptedAt)
    )->toBeTrue();
});

test('stale EULA page cannot be recorded as acceptance of a newer version', function () {
    $user = createLegalTestDoctor();

    config([
        'legal.eula_version' => '2.0',
    ]);

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), [
            'agree' => '1',
            'document_version' => '1.0',
        ])
        ->assertRedirect(route('legal.eula'))
        ->assertSessionHasErrors('document_version');

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => '2.0',
    ]);

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => '1.0',
    ]);
});

test('privacy acceptance cannot be submitted before current EULA acceptance', function () {
    $user = createLegalTestDoctor();

    $privacyVersion = (string) config('legal.privacy_version');

    $this
        ->actingAs($user)
        ->post(route('legal.privacy.accept'), [
            'acknowledge' => '1',
            'document_version' => $privacyVersion,
        ])
        ->assertRedirect(route('legal.eula'));

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => $privacyVersion,
    ]);
});

test('stale privacy page cannot be recorded as acceptance of a newer version', function () {
    $user = createLegalTestDoctor();

    $eulaVersion = (string) config('legal.eula_version');

    LegalAcceptance::create([
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => $eulaVersion,
        'accepted_at' => now(),
    ]);

    config([
        'legal.privacy_version' => '2.0',
    ]);

    $this
        ->actingAs($user)
        ->post(route('legal.privacy.accept'), [
            'acknowledge' => '1',
            'document_version' => '1.0',
        ])
        ->assertRedirect(route('legal.privacy'))
        ->assertSessionHasErrors('document_version');

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => '2.0',
    ]);

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => '1.0',
    ]);
});

test('completed legal onboarding returns doctor to original protected page', function () {
    $user = createLegalTestDoctor();

    $eulaVersion = (string) config('legal.eula_version');
    $privacyVersion = (string) config('legal.privacy_version');

    $this
        ->actingAs($user)
        ->get(route('screening'))
        ->assertRedirect(route('legal.eula'));

    expect(session('url.intended'))->toBe('/screening');

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), [
            'agree' => '1',
            'document_version' => $eulaVersion,
        ])
        ->assertRedirect(route('legal.privacy'));

    $this
        ->actingAs($user)
        ->post(route('legal.privacy.accept'), [
            'acknowledge' => '1',
            'document_version' => $privacyVersion,
        ])
        ->assertRedirect('/screening');

    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => $eulaVersion,
    ]);

    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => $privacyVersion,
    ]);
});

test('profile remains available before legal acceptance', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('authorized doctor can access application downloads without Web legal acceptance', function () {
    Role::findOrCreate('doctor', 'web');

    $user = User::factory()->create();

    $user->assignRole('doctor');

    Storage::fake('app_downloads');

    $this
        ->actingAs($user)
        ->get(route('mobile-app'))
        ->assertOk();

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
    ]);
});

test('legal middleware fails closed when EULA version is not configured', function () {
    $user = createLegalTestDoctor();

    config([
        'legal.eula_version' => '',
    ]);

    $this
        ->actingAs($user)
        ->get(route('screening'))
        ->assertStatus(500);

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => '',
    ]);
});

test('legal middleware fails closed when privacy version is not configured', function () {
    $user = createLegalTestDoctor();

    config([
        'legal.privacy_version' => '   ',
    ]);

    $this
        ->actingAs($user)
        ->get(route('screening'))
        ->assertStatus(500);

    $this->assertDatabaseMissing('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => '',
    ]);
});

test('new EULA does not require re-acknowledgement of unchanged privacy notice', function () {
    $user = createLegalTestDoctor();

    config([
        'legal.eula_version' => '2.0',
        'legal.privacy_version' => '1.0',
    ]);

    LegalAcceptance::create([
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => '1.0',
        'accepted_at' => now()->subDay(),
    ]);

    LegalAcceptance::create([
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => '1.0',
        'accepted_at' => now()->subDay(),
    ]);

    $privacyAcceptance = LegalAcceptance::query()
        ->where('user_id', $user->id)
        ->where('document_type', 'privacy')
        ->where('document_version', '1.0')
        ->firstOrFail();

    $originalPrivacyAcceptedAt = $privacyAcceptance->accepted_at;

    $this
        ->actingAs($user)
        ->get(route('screening'))
        ->assertRedirect(route('legal.eula'));

    expect(session('url.intended'))->toBe('/screening');

    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), [
            'agree' => '1',
            'document_version' => '2.0',
        ])
        ->assertRedirect('/screening');

    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => '2.0',
    ]);

    expect(
        LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('document_type', 'privacy')
            ->where('document_version', '1.0')
            ->count()
    )->toBe(1);

    expect(
        $privacyAcceptance
            ->fresh()
            ->accepted_at
            ->equalTo($originalPrivacyAcceptedAt)
    )->toBeTrue();
});

test('legal onboarding pages do not link the brand into protected clinical routes', function () {
    Role::findOrCreate('admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('admin');

    /*
     * The EULA can be viewed before any legal acceptance exists.
     */
    $eula = $this
        ->actingAs($user)
        ->get(route('legal.eula'));

    $eula
        ->assertOk()
        ->assertDontSee('href="' . route('history') . '"', false)
        ->assertDontSee('href="' . route('welcome') . '"', false);

    /*
     * The Privacy Notice intentionally requires acceptance of the
     * current EULA first. Create only that prerequisite so we can
     * inspect the Privacy page itself without bypassing its flow.
     */
    LegalAcceptance::create([
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => (string) config('legal.eula_version'),
        'accepted_at' => now(),
    ]);

    $privacy = $this
        ->actingAs($user)
        ->get(route('legal.privacy'));

    $privacy
        ->assertOk()
        ->assertDontSee('href="' . route('history') . '"', false)
        ->assertDontSee('href="' . route('welcome') . '"', false);
});

test('admin completes Web legal onboarding and returns to clinical history', function () {
    Role::findOrCreate('admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('admin');

    $eulaVersion = (string) config('legal.eula_version');
    $privacyVersion = (string) config('legal.privacy_version');

    /*
     * An administrator entering the shared RETINA Web clinical area
     * must complete the same current Web legal onboarding.
     */
    $this
        ->actingAs($user)
        ->get(route('history'))
        ->assertRedirect(route('legal.eula'));

    expect(session('url.intended'))->toBe('/history');

    /*
     * Accept the current EULA.
     */
    $this
        ->actingAs($user)
        ->post(route('legal.eula.accept'), [
            'agree' => '1',
            'document_version' => $eulaVersion,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('legal.privacy'));

    /*
     * Accept the current Privacy Notice.
     */
    $this
        ->actingAs($user)
        ->post(route('legal.privacy.accept'), [
            'acknowledge' => '1',
            'document_version' => $privacyVersion,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/history');

    /*
     * Both acceptance records must exist for the administrator.
     */
    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'eula',
        'document_version' => $eulaVersion,
    ]);

    $this->assertDatabaseHas('legal_acceptances', [
        'user_id' => $user->id,
        'document_type' => 'privacy',
        'document_version' => $privacyVersion,
    ]);

    /*
     * Once onboarding is complete, clinical history must be reachable.
     */
    $this
        ->actingAs($user)
        ->get(route('history'))
        ->assertOk();
});