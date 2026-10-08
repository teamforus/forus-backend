<?php

return [
    'identity_provider_login' => [
        'subject' => 'Informatie over inloggen',
        'webshop' => [
            'formal' => 'Log in via Microsoft op de webshop die uw organisatie hiervoor gebruikt. '
                . 'De onderstaande link opent de webshop waar u de inlogpoging heeft gedaan.',
            'informal' => 'Log in via Microsoft op de webshop die je organisatie hiervoor gebruikt. '
                . 'De onderstaande link opent de webshop waar je de inlogpoging hebt gedaan.',
            'link' => 'Naar de webshop',
        ],
        'me_app' => [
            'formal' => "U kunt de Me-app koppelen via de webshop:\n\n"
                . "1. Log in via Microsoft op de webshop van uw organisatie.\n"
                . "2. Open in de Me-app het scherm voor inloggen vanaf een ander apparaat, zodat u een code ziet.\n"
                . "3. Open het gebruikersmenu in de webshop en kies 'Log in op de app'.\n"
                . '4. Vul daar de code uit de Me-app in en bevestig het koppelen.',
            'informal' => "Je kunt de Me-app koppelen via de webshop:\n\n"
                . "1. Log in via Microsoft op de webshop van je organisatie.\n"
                . "2. Open in de Me-app het scherm voor inloggen vanaf een ander apparaat, zodat je een code ziet.\n"
                . "3. Open het gebruikersmenu in de webshop en kies 'Log in op de app'.\n"
                . '4. Vul daar de code uit de Me-app in en bevestig het koppelen.',
        ],
        'dashboard' => [
            'formal' => 'Dit account is bedoeld voor gebruik als aanvrager en geeft geen toegang tot het dashboard. '
                . 'Neem bij vragen contact op met uw organisatie.',
            'informal' => 'Dit account is bedoeld voor gebruik als aanvrager en geeft geen toegang tot het dashboard. '
                . 'Neem bij vragen contact op met je organisatie.',
            'link' => 'Naar het dashboard',
        ],
        'inactive' => [
            'formal' => 'Uw account is niet actief. U kunt momenteel niet inloggen. '
                . 'Neem contact op met uw organisatie als u weer toegang nodig heeft.',
            'informal' => 'Je account is niet actief. Je kunt momenteel niet inloggen. '
                . 'Neem contact op met je organisatie als je weer toegang nodig hebt.',
        ],
        'other' => [
            'formal' => 'Neem contact op met uw organisatie voor informatie over toegang tot uw account.',
            'informal' => 'Neem contact op met je organisatie voor informatie over toegang tot je account.',
        ],
    ],
    'login' => [],
    'user' => [],
    'validations' => [],
    'vouchers' => [
        'voucher_sent' => [
            'subject' => 'Uw tegoed',
            'title' => 'Uw tegoed',
        ],
    ],
    'reservations' => [
        'extra_payment' => [
            'refunded_body' => 'De bijbetaling wordt binnen 14 dagen teruggestort.',
        ],
    ],
];
