<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Applicant Email Verification
    |--------------------------------------------------------------------------
    |
    | A professional-access request must verify its submitted email address
    | before an administrator is allowed to review it.
    |
    | The verification URL is a temporary Laravel signed URL. No plaintext
    | verification token is stored in the database.
    |
    */

    'email_verification' => [

        'expires_minutes' => (int) env(
            'RETINA_ACCESS_VERIFICATION_EXPIRES_MINUTES',
            1440
        ),

    ],

];
