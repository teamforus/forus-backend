<?php

return [
    'from' => [
        'no-reply' => env('MAIL_FROM_ADDRESS', 'no-reply@forus.io'),
        'name' => env('MAIL_FROM_NAME', 'Stichting Forus')
    ],
    'email-preferences-link' => env('EMAIL_PREFERENCES_LINK'),
    'email-not-you-link' => env('EMAIL_NOT_YOU_LINK')
];
