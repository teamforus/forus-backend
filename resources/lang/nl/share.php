<?php

return [
    'sms' => [
        'me_app_download_link' => [
            'failed' => 'Het is niet gelukt om u een sms bericht te sturen.',
            'messages' => sprintf(
                'Download Me makkelijk via de link: %s',
                env('ME_APP_SMS_DOWNLOAD_LINK', 'https://www.forus.io/DL')
            ),
        ],
    ],
];
