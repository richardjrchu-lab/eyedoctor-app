<?php

use App\Models\LegalAcceptance;
use App\Models\User;
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
        $firstAcceptance->fresh()->accepted_at->equalTo($originalAcceptedAt)
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

    /*
     * This request is blocked by legal.accepted and should store only
     * the internal /screening URI as the intended destination.
     */
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