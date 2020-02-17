<?php

return [
    'used' => [
        'title' => "This activation code was already used.",
        'message' => "This activation code was already used.",
    ],
    'used_same_fund' => [
        'title' => 'U heeft een voucher voor deze regeling!',
        'message' => implode('', [
            "Gebruik voor iedere individuele aanvraag een apart account. " .
            "Wilt u een tweede code activeren, gebruik hiervoor een nieuw e-mailadres."
        ]),
    ],
];