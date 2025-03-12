<?php

return [
    'accept_reservation' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'auth' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'auth_2fa' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'delete_identity' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'email' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'feedback_form' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'fund_check' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'fund_custom_notification' => [
        'to_many_attempts' => [
            'title' => 'Te veel uitnodigingen!',
            'message' => implode("\n", [
                'U heeft het maximaal aantal uitnodigingen dat u kunt versturen bereikt. ',
                'Probeer het over :available_in_min minuten opnieuw.',
            ]),
        ],
    ],
    'invite_employee' => [
        'to_many_attempts' => [
            'title' => 'Te veel uitnodigingen!',
            'message' => implode("\n", [
                'U heeft het maximaal aantal uitnodigingen dat u kunt versturen bereikt. ',
                'Probeer het over :available_in_min minuten opnieuw.',
            ]),
        ],
    ],
    'make_transaction' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Sorry, u kunt maar 1 transactie per tegoed uitvoeren binnen :decay_seconds seconden. ' .
                "\nProbeer het opnieuw over :available_in_sec seconden.",
        ],
    ],
    'mollie_connection' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'mollie_connection_profile' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'physical_card_requests' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'physical_cards' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'prevalidations' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => implode("\n", [
                'U heeft driemaal een verkeerde activatiecode ingevuld. ',
                'Probeer het over :available_in_min minuten opnieuw.',
            ]),
        ],
        'not_found' => [
            'title' => 'U heeft een tegoed voor dit fonds!',
            'message' => implode("\n", [
                'U heeft een verkeerde of gebruikte activatiecode ingevuld. ' .
                'Dit is uw :attempts poging uit :max_attempts waarna u voor :decay_minutes minuten geblokeerd wordt.',
            ]),
        ],
        'used' => [
            'title' => 'Gebruikt!',
            'message' => implode("\n", [
                'Deze code is al gebruikt.',
                'Dit is uw :attempts poging uit :max_attempts waarna u voor :decay_minutes minuten geblokeerd wordt.',
            ]),
        ],
    ],
    'provider_reservation_store' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'reservation_extra_payment' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'share_app_email' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'share_app_sms' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'update_profile' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
    'voucher_transaction_bulks' => [
        'to_many_attempts' => [
            'title' => 'Te veel pogingen!',
            'message' => 'Dit is poging :max_attempts, probeer het over :available_in_min minuten opnieuw.',
        ],
    ],
];
