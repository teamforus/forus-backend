<?php

return [
    'mappings' => [
        'default' => [
            'fields' => [
                'bsn' => [
                    'column' => 'bsn',
                    'required' => true,
                    'normalize' => 'bsn',
                ],
                'client_number' => [
                    'column' => 'client_number',
                    'required' => false,
                ],
                'given_name' => [
                    'column' => 'given_name',
                    'required' => false,
                ],
                'family_name' => [
                    'column' => 'family_name',
                    'required' => false,
                ],
                'birth_date' => [
                    'column' => 'birth_date',
                    'required' => false,
                ],
                'gender' => [
                    'column' => 'gender',
                    'required' => false,
                ],
                'street' => [
                    'column' => 'street',
                    'required' => false,
                ],
                'house_number' => [
                    'column' => 'house_number',
                    'required' => false,
                ],
                'house_number_addition' => [
                    'column' => 'house_number_addition',
                    'required' => false,
                ],
                'postal_code' => [
                    'column' => 'postal_code',
                    'required' => false,
                ],
                'city' => [
                    'column' => 'city',
                    'required' => false,
                ],
            ],
        ],
        'sponsor_persons' => [
            'fields' => [
                'bsn' => [
                    'column' => 'bsn',
                    'required' => true,
                    'normalize' => 'bsn',
                ],
                'client_number' => [
                    'column' => 'clientnummer',
                    'required' => true,
                ],
                'given_name' => [
                    'column' => 'voornaam',
                    'required' => true,
                ],
                'initials' => [
                    'column' => 'voorletters',
                    'required' => true,
                ],
                'family_name_prefix' => [
                    'column' => 'tussenvoegsel',
                    'required' => true,
                ],
                'family_name' => [
                    'column' => 'achternaam',
                    'required' => true,
                ],
                'death_date' => [
                    'column' => 'overlijdensdatum',
                    'required' => false,
                    'date_format' => 'dd-MM-yyyy',
                ],
                'reference_person_bsn' => [
                    'column' => 'referentiepersoon',
                    'required' => true,
                    'normalize' => 'bsn',
                ],
                'household_role' => [
                    'column' => 'gezinslid_soort',
                    'required' => true,
                    'values' => [
                        'Hoofd' => 'head',
                        'Partner' => 'partner',
                        'Kind' => 'child',
                    ],
                ],
                'birth_date' => [
                    'column' => 'geboortedatum',
                    'required' => true,
                    'date_format' => 'dd-MM-yyyy',
                ],
                'gender' => [
                    'column' => 'geslacht',
                    'required' => true,
                    'values' => [
                        'Man' => 'mannelijk',
                        'Vrouw' => 'vrouwelijk',
                        'Niet gespecificeerd' => 'niet gespecificeerd',
                        'Onbekend' => 'onbekend',
                    ],
                ],
                'companion_pass' => [
                    'column' => 'begeleiderspas',
                    'required' => true,
                    'values' => [
                        'TRUE' => 'Ja',
                        'FALSE' => 'Nee',
                    ],
                ],
                'has_pet' => [
                    'column' => 'heeft_huisdier',
                    'required' => false,
                    'values' => [
                        'TRUE' => 'Ja',
                        'FALSE' => 'Nee',
                    ],
                ],
                'municipality_code' => [
                    'column' => 'gemeentecode',
                    'required' => true,
                ],
                'street' => [
                    'column' => 'woon_straatnaam',
                    'required' => true,
                ],
                'house_number' => [
                    'column' => 'woon_huisnummer',
                    'required' => true,
                ],
                'house_letter' => [
                    'column' => 'woon_huisletter',
                    'required' => false,
                ],
                'house_number_addition' => [
                    'column' => 'woon_huisnummertoevoeging',
                    'required' => false,
                ],
                'postal_code' => [
                    'column' => 'woon_postcode',
                    'required' => true,
                ],
                'city' => [
                    'column' => 'woon_plaatsnaam',
                    'required' => true,
                ],
                'mailing_street' => [
                    'column' => 'post_straatnaam',
                    'required' => true,
                ],
                'mailing_house_number' => [
                    'column' => 'post_huisnummer',
                    'required' => true,
                ],
                'mailing_house_letter' => [
                    'column' => 'post_huisletter',
                    'required' => false,
                ],
                'mailing_house_number_addition' => [
                    'column' => 'post_huisnummertoevoeging',
                    'required' => false,
                ],
                'mailing_postal_code' => [
                    'column' => 'post_postcode',
                    'required' => true,
                ],
                'mailing_city' => [
                    'column' => 'post_plaatsnaam',
                    'required' => true,
                ],
            ],
        ],
    ],
];
