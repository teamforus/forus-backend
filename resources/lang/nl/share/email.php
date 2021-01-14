<?php

$downloadLink = env('ME_APP_SMS_DOWNLOAD_LINK', 'https://www.forus.io/DL');

return [
    'me_app_download_link' => [
        'title' => 'Downloadlink Me-app',
        'line_1' => 'Download de app via de link:',
        'line_2' => 'Aan het gebruik van de app zijn geen kosten verbonden.',
        'link' => $downloadLink,
    ],
];
