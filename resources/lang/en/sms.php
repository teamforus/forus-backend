<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Error sending sms',

    'message' => [
        'me_app_download_link' => sprintf(
            "Visit %s to download the Me-app",
            env('ME_APP_SMS_DOWNLOAD_LINK', 'https://www.forus.io/DL')
        ),
    ]
];
