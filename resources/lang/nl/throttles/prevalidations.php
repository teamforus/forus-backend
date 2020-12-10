<?php

return [
    'to_many_attempts' => [
        'title' => "Te veel pogingen!",
        'message' => implode("\n", [
            "U heeft driemaal een verkeerde activatiecode ingevuld. ",
            "Probeer het over :available_in_min minuten opnieuw.",
        ]),
    ],
    'not_found' => [
        'title' => 'U heeft een tegoed voor dit fonds!',
        'message' => implode("\n", [
            "U heeft een verkeerde of gebruikte activatiecode ingevuld. " .
            "Dit is uw :attempts poging uit :max_attempts waarna u voor :decay_minutes minuten geblokeerd wordt."
        ]),
    ],
    'used' => [
        'title' => 'Used!',
        'message' => implode("\n", [
            "The code is already used. ",
            "Dit is uw :attempts poging uit :max_attempts waarna u voor :decay_minutes minuten geblokeerd wordt."
        ]),
    ],
];