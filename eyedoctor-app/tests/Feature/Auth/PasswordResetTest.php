<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response
        ->assertStatus(200)
        ->assertSee('Forgot Password')
        ->assertSee('Send Password Reset Link')
        ->assertSee('action="'.route('password.email').'"', false)
        ->assertSee('href="'.route('login').'"', false);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});


test('reset password screen preserves the token and email from the reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('name="token"', false)
            ->assertSee('value="'.$notification->token.'"', false)
            ->assertSee('value="'.$user->email.'"', false)
            ->assertSee('action="'.route('password.store').'"', false)
            ->assertSee('name="_token"', false);

        return true;
    });
});

test('reset password email uses RETINA branding and the standard reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $mail = $notification->toMail($user);

        $expectedUrl = url(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ], false));

        expect($mail->from)->toBe([config('mail.from.address'), 'RETINA'])
            ->and($mail->subject)->toBe('Reset your RETINA password');

        $html = (string) $mail->render();

        expect($html)
            ->toContain('RETINA')
            ->toContain('Diabetic Retinopathy Detection System')
            ->toContain(e($expectedUrl))
            ->not->toContain('Laravel');

        return true;
    });
});

test('password cannot be reset with an invalid token', function () {
    $user = User::factory()->create();

    $response = $this
        ->from('/reset-password/invalid-token')
        ->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

    $response
        ->assertRedirect('/reset-password/invalid-token')
        ->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
