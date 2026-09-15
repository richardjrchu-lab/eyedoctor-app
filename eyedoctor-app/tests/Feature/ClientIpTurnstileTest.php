<?php

use App\Services\TurnstileVerifier;
use Illuminate\Http\UploadedFile;

test(
    'professional access submission sends the resolved visitor IP to Turnstile',
    function () {
        $turnstile = $this->mock(
            TurnstileVerifier::class
        );

        $turnstile
            ->shouldReceive('verify')
            ->once()
            ->with(
                'submission-test-token',
                '203.0.113.60'
            )
            ->andReturn(false);

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '::1',
            ])
            ->withHeaders([
                'CF-Connecting-IP' => '203.0.113.60',
            ])
            ->from('/request-access')
            ->post(
                '/request-access',
                [
                    'full_name' => 'Turnstile Test Professional',

                    'email' => 'turnstile-submit@example.test',

                    'profession' => 'physician',

                    'institution' => 'RETINA Test Medical Center',

                    'department_position' => 'Ophthalmology',

                    'license_registration_number' => null,

                    'proof_type' => 'institution_id',

                    'proof_document' => UploadedFile::fake()->create(
                        'proof.png',
                        1,
                        'image/png'
                    ),

                    'cf-turnstile-response' => 'submission-test-token',

                    'privacy_consent' => '1',

                    'appropriate_use_consent' => '1',
                ]
            );

        $response->assertRedirect(
            '/request-access'
        );

        $response->assertSessionHasErrors(
            'cf-turnstile-response'
        );
    }
);

test(
    'verification resend sends the resolved visitor IP to Turnstile',
    function () {
        $turnstile = $this->mock(
            TurnstileVerifier::class
        );

        $turnstile
            ->shouldReceive('verify')
            ->once()
            ->with(
                'resend-test-token',
                '198.51.100.61'
            )
            ->andReturn(false);

        $response = $this
            ->withServerVariables([
                'REMOTE_ADDR' => '::1',
            ])
            ->withHeaders([
                'CF-Connecting-IP' => '198.51.100.61',
            ])
            ->from(
                '/request-access/resend-verification'
            )
            ->post(
                '/request-access/resend-verification',
                [
                    'email' => 'turnstile-resend@example.test',

                    'cf-turnstile-response' => 'resend-test-token',
                ]
            );

        $response->assertRedirect(
            '/request-access/resend-verification'
        );

        $response->assertSessionHasErrors(
            'cf-turnstile-response'
        );
    }
);
