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

    'failed' => 'Het is niet gelukt om u een sms bericht te sturen.',

    'messages' => [
        'me_app_download_link' => sprintf(
            "Download Me makkelijk via de link: %s",
            env('ME_APP_SMS_DOWNLOAD_LINK', 'https://www.forus.io/DL')
        ),
    ]
];
