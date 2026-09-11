<?php

test('public registration screen is disabled', function () {
    $this
        ->get('/register')
        ->assertNotFound();
});

test('public registration submission is disabled', function () {
    $email = 'unauthorized-registration@example.test';

    $this
        ->post('/register', [
            'name' => 'Unauthorized Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertNotFound();

    $this->assertGuest();

    $this->assertDatabaseMissing(
        'users',
        [
            'email' => $email,
        ]
    );
});
