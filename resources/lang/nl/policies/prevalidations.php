<?php

return [
    'used' => [
        'title' => "Activeringscode al gebruikt",
        'message' => "Deze activeringscode is al gebruikt. Gebruik een andere code..",
    ],
    'used_same_fund' => [
        'title' => 'U heeft een voucher voor deze regeling!',
        'message' => implode('', [
            "Gebruik voor iedere individuele aanvraag een apart account. " .
            "Wilt u een tweede code activeren, gebruik hiervoor een nieuw e-mailadres."
        ]),
    ],
];