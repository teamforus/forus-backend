<?php

use Carbon\Carbon;

$today = Carbon::today();

$makeBirthData = static function (int $age) use ($today): array {
    $date = $today->copy()->subYears($age);

    return [
        'leeftijd' => $age,
        'geboorte' => [
            'datum' => [
                'datum' => $date->format('Y-m-d'),
                'jaar' => (int) $date->format('Y'),
                'maand' => (int) $date->format('m'),
                'dag' => (int) $date->format('d'),
            ],
        ],
    ];
};

$default = [
    999993112 => [
        '_embedded' => [
            'kinderen' => [
                ['burgerservicenummer' => 999995807],
                ['burgerservicenummer' => 999995832],
                ['burgerservicenummer' => 123456782],
            ],

            'partners' => [['burgerservicenummer' => 999994542]],
        ],

        'burgerservicenummer' => 999993112,
        'aNummer' => 2363230194,
        'geslachtsaanduiding' => 'vrouw',
        ...$makeBirthData(55),
        'naam' => [
            'geslachtsnaam' => 'Zon',
            'voorletters' => 'W.',
            'voornamen' => 'Wilma',
            'voorvoegsel' => 'van',
            'aanschrijfwijze' => 'W. den Braber - van Zon',
            'aanduidingNaamgebruik' => 'partner_eigen',
        ],
        'verblijfplaats' => [
            'straat' => 'Schakelstraat',
            'huisnummer' => 14,
            'huisnummertoevoeging' => 'H',
            'postcode' => '1014AW',
            'woonplaats' => 'Amsterdam',
            'nummeraanduidingIdentificatie' => '0363200000264601',
            'functieAdres' => 'woonadres',
            'korteNaam' => 'Schakelstraat',
            'adresregel1' => 'Schakelstraat 14 H',
            'adresregel2' => '1014AW Amsterdam',
        ],
    ],

    999994542 => [
        '_embedded' => [
            'kinderen' => [
                ['burgerservicenummer' => 999995807],
                ['burgerservicenummer' => 999995832],
                ['burgerservicenummer' => 123456782],
            ],

            'partners' => [['burgerservicenummer' => 999993112]],
        ],
        'burgerservicenummer' => 999994542,
        'aNummer' => 2363230194,
        'geslachtsaanduiding' => 'man',
        ...$makeBirthData(57),
        'naam' => [
            'geslachtsnaam' => 'Braber',
            'voorletters' => 'G.',
            'voornamen' => 'Gerrit',
            'voorvoegsel' => 'den',
            'aanschrijfwijze' => 'G. den Braber - den Gerrit',
            'aanduidingNaamgebruik' => 'partner_eigen',
        ],
        'verblijfplaats' => [
            'straat' => 'Schakelstraat',
            'huisnummer' => 14,
            'huisnummertoevoeging' => 'H',
            'postcode' => '1014AW',
            'woonplaats' => 'Amsterdam',
            'nummeraanduidingIdentificatie' => '0363200000264601',
            'functieAdres' => 'woonadres',
            'korteNaam' => 'Schakelstraat',
            'adresregel1' => 'Schakelstraat 14 H',
            'adresregel2' => '1014AW Amsterdam',
        ],
    ],

    999995807 => [
        '_embedded' => [],
        'burgerservicenummer' => 999995807,
        'aNummer' => 2363230194,
        'geslachtsaanduiding' => 'vrouw',
        ...$makeBirthData(6),
        'naam' => [
            'geslachtsnaam' => 'Braber',
            'voorletters' => 'Z.',
            'voornamen' => 'Zoey',
            'voorvoegsel' => 'den',
        ],
        'verblijfplaats' => [
            'straat' => 'Schakelstraat',
            'huisnummer' => 14,
            'huisnummertoevoeging' => 'H',
            'postcode' => '1014AW',
            'woonplaats' => 'Amsterdam',
            'nummeraanduidingIdentificatie' => '0363200000264601',
            'functieAdres' => 'woonadres',
            'korteNaam' => 'Schakelstraat',
            'adresregel1' => 'Schakelstraat 14 H',
            'adresregel2' => '1014AW Amsterdam',
        ],
    ],

    999995832 => [
        '_embedded' => [],
        'burgerservicenummer' => 999995832,
        'aNummer' => 2363230194,
        'geslachtsaanduiding' => 'vrouw',
        ...$makeBirthData(6),
        'naam' => [
            'geslachtsnaam' => 'Braber',
            'voorletters' => 'A.',
            'voornamen' => 'Alexander',
            'voorvoegsel' => 'den',
        ],
        'verblijfplaats' => [
            'straat' => 'Schakelstraat',
            'huisnummer' => 14,
            'huisnummertoevoeging' => 'H',
            'postcode' => '1014AW',
            'woonplaats' => 'Amsterdam',
            'nummeraanduidingIdentificatie' => '0363200000264601',
            'functieAdres' => 'woonadres',
            'korteNaam' => 'Schakelstraat',
            'adresregel1' => 'Schakelstraat 14 H',
            'adresregel2' => '1014AW Amsterdam',
        ],
    ],

    123456782 => [
        '_embedded' => [],
        'burgerservicenummer' => 123456782,
        'aNummer' => 2363230194,
        'geslachtsaanduiding' => 'vrouw',
        ...$makeBirthData(14),
        'naam' => [
            'geslachtsnaam' => 'Braber',
            'voorletters' => 'A.',
            'voornamen' => 'Alexander',
            'voorvoegsel' => 'den',
        ],
        'verblijfplaats' => [
            'straat' => 'Schakelstraat',
            'huisnummer' => 14,
            'huisnummertoevoeging' => 'H',
            'postcode' => '1014AW',
            'woonplaats' => 'Amsterdam',
            'nummeraanduidingIdentificatie' => '0363200000264601',
            'functieAdres' => 'woonadres',
            'korteNaam' => 'Schakelstraat',
            'adresregel1' => 'Schakelstraat 14 H',
            'adresregel2' => '1014AW Amsterdam',
        ],
    ],

    216506414 => [
        '_embedded' => [],
    ],
];

$missed_records = $default;
unset($missed_records['999993112']['geboorte']);
unset($missed_records['999993112']['leeftijd']);
unset($missed_records['999993112']['naam']['voornamen']);
unset($missed_records['999994542']['geboorte']);
unset($missed_records['999994542']['leeftijd']);
unset($missed_records['123456782']['geboorte']);
unset($missed_records['123456782']['leeftijd']);

return [
    'default' => env('PERSON_BSN_API_SERVICE', 'iconnect'),
    'fund_prefill_cache_time' => env('PERSON_BSN_FUND_PREFILL_CACHE_TIME', 60 * 15),
    'test_response' => env('PERSON_BSN_TEST_RESPONSE', false),
    'test_response_profile' => env('PERSON_BSN_TEST_RESPONSE_PROFILE', 'default'),

    'test_response_data' => [
        'default' => $default,

        'missed_records' => $missed_records,

        'custom' => [
            900244136 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900244148]],
                    'kinderen' => [
                        ['burgerservicenummer' => 900244161],
                    ],
                ],
                'burgerservicenummer' => 900244136,
                'aNummer' => 2363230195,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(40),
                'naam' => [
                    'geslachtsnaam' => 'Jansen',
                    'voorletters' => 'P.',
                    'voornamen' => 'Pieter',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'P. Jansen',
                    'aanduidingNaamgebruik' => 'eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Lindelaan',
                    'huisnummer' => 22,
                    'postcode' => '1234AB',
                    'woonplaats' => 'Utrecht',
                    'nummeraanduidingIdentificatie' => '1234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Lindelaan',
                    'adresregel1' => 'Lindelaan 22',
                    'adresregel2' => '1234AB Utrecht',
                ],
            ],

            900244148 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900244136]],
                    'kinderen' => [
                        ['burgerservicenummer' => 900244161],
                    ],
                ],
                'burgerservicenummer' => 900244148,
                'aNummer' => 2363230196,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(38),
                'naam' => [
                    'geslachtsnaam' => 'De Vries',
                    'voorletters' => 'M.',
                    'voornamen' => 'Maria',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'M. Jansen - de Vries',
                    'aanduidingNaamgebruik' => 'partner_eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Lindelaan',
                    'huisnummer' => 22,
                    'postcode' => '1234AB',
                    'woonplaats' => 'Utrecht',
                    'nummeraanduidingIdentificatie' => '1234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Lindelaan',
                    'adresregel1' => 'Lindelaan 22',
                    'adresregel2' => '1234AB Utrecht',
                ],
            ],

            900244161 => [
                '_embedded' => [],
                'burgerservicenummer' => 900244161,
                'aNummer' => 2363230197,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(10),
                'naam' => [
                    'geslachtsnaam' => 'Jansen',
                    'voorletters' => 'T.',
                    'voornamen' => 'Tom',
                    'voorvoegsel' => '',
                ],
                'verblijfplaats' => [
                    'straat' => 'Lindelaan',
                    'huisnummer' => 22,
                    'postcode' => '1234AB',
                    'woonplaats' => 'Utrecht',
                    'nummeraanduidingIdentificatie' => '1234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Lindelaan',
                    'adresregel1' => 'Lindelaan 22',
                    'adresregel2' => '1234AB Utrecht',
                ],
            ],

            900244197 => [
                '_embedded' => [],
                'burgerservicenummer' => 900244197,
                'aNummer' => 2363230198,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(25),
                'naam' => [
                    'geslachtsnaam' => 'Bakker',
                    'voorletters' => 'L.',
                    'voornamen' => 'Lisa',
                    'voorvoegsel' => '',
                ],
                'verblijfplaats' => [
                    'straat' => 'Stationsweg',
                    'huisnummer' => 5,
                    'postcode' => '5678CD',
                    'woonplaats' => 'Eindhoven',
                    'nummeraanduidingIdentificatie' => '2234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Stationsweg',
                    'adresregel1' => 'Stationsweg 5',
                    'adresregel2' => '5678CD Eindhoven',
                ],
            ],

            900203390 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900203407]],
                ],
                'burgerservicenummer' => 900203390,
                'aNummer' => 2363230199,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(65),
                'naam' => [
                    'geslachtsnaam' => 'Mulder',
                    'voorletters' => 'H.',
                    'voornamen' => 'Henk',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'H. Mulder',
                    'aanduidingNaamgebruik' => 'eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Dorpsstraat',
                    'huisnummer' => 101,
                    'postcode' => '9101EF',
                    'woonplaats' => 'Dokkum',
                    'nummeraanduidingIdentificatie' => '3234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Dorpsstraat',
                    'adresregel1' => 'Dorpsstraat 101',
                    'adresregel2' => '9101EF Dokkum',
                ],
            ],

            900203407 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900203390]],
                ],
                'burgerservicenummer' => 900203407,
                'aNummer' => 2363230200,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(63),
                'naam' => [
                    'geslachtsnaam' => 'Mulder',
                    'voorletters' => 'A.',
                    'voornamen' => 'Annie',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'A. Mulder',
                    'aanduidingNaamgebruik' => 'eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Dorpsstraat',
                    'huisnummer' => 101,
                    'postcode' => '9101EF',
                    'woonplaats' => 'Dokkum',
                    'nummeraanduidingIdentificatie' => '3234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Dorpsstraat',
                    'adresregel1' => 'Dorpsstraat 101',
                    'adresregel2' => '9101EF Dokkum',
                ],
            ],

            900158074 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900158086]],
                    'kinderen' => [
                        ['burgerservicenummer' => 999993847],
                        ['burgerservicenummer' => 999992077],
                        ['burgerservicenummer' => 999993483],
                        ['burgerservicenummer' => 999990482],
                        ['burgerservicenummer' => 999993653],
                        ['burgerservicenummer' => 999995017],
                        ['burgerservicenummer' => 999990408],
                    ],
                ],
                'burgerservicenummer' => 900158074,
                'aNummer' => 2363230201,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(52),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'R.',
                    'voornamen' => 'Robert',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'R. Smit',
                    'aanduidingNaamgebruik' => 'eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            900158086 => [
                '_embedded' => [
                    'partners' => [['burgerservicenummer' => 900158074]],
                    'kinderen' => [
                        ['burgerservicenummer' => 999993847],
                        ['burgerservicenummer' => 999992077],
                        ['burgerservicenummer' => 999993483],
                        ['burgerservicenummer' => 999990482],
                        ['burgerservicenummer' => 999993653],
                        ['burgerservicenummer' => 999995017],
                        ['burgerservicenummer' => 999990408],
                    ],
                ],
                'burgerservicenummer' => 900158086,
                'aNummer' => 2363230202,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(49),
                'naam' => [
                    'geslachtsnaam' => 'Visser',
                    'voorletters' => 'E.',
                    'voornamen' => 'Elise',
                    'voorvoegsel' => '',
                    'aanschrijfwijze' => 'E. Smit - Visser',
                    'aanduidingNaamgebruik' => 'partner_eigen',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999993847 => [
                '_embedded' => [],
                'burgerservicenummer' => 999993847,
                'aNummer' => 2363230203,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(24),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'L.',
                    'voornamen' => 'Lotte',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999992077 => [
                '_embedded' => [],
                'burgerservicenummer' => 999992077,
                'aNummer' => 2363230204,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(21),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'D.',
                    'voornamen' => 'Daan',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999993483 => [
                '_embedded' => [],
                'burgerservicenummer' => 999993483,
                'aNummer' => 2363230205,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(19),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'S.',
                    'voornamen' => 'Sanne',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999990482 => [
                '_embedded' => [],
                'burgerservicenummer' => 999990482,
                'aNummer' => 2363230206,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(17),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'T.',
                    'voornamen' => 'Tim',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999993653 => [
                '_embedded' => [],
                'burgerservicenummer' => 999993653,
                'aNummer' => 2363230208,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(12),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'M.',
                    'voornamen' => 'Milan',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999995017 => [
                '_embedded' => [],
                'burgerservicenummer' => 999995017,
                'aNummer' => 2363230209,
                'geslachtsaanduiding' => 'vrouw',
                ...$makeBirthData(8),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'F.',
                    'voornamen' => 'Fenna',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],

            999990408 => [
                '_embedded' => [],
                'burgerservicenummer' => 999990408,
                'aNummer' => 2363230210,
                'geslachtsaanduiding' => 'man',
                ...$makeBirthData(5),
                'naam' => [
                    'geslachtsnaam' => 'Smit',
                    'voorletters' => 'J.',
                    'voornamen' => 'Jesse',
                ],
                'verblijfplaats' => [
                    'straat' => 'Beukenlaan',
                    'huisnummer' => 88,
                    'postcode' => '3456GH',
                    'woonplaats' => 'Amersfoort',
                    'nummeraanduidingIdentificatie' => '4234567890123456',
                    'functieAdres' => 'woonadres',
                    'korteNaam' => 'Beukenlaan',
                    'adresregel1' => 'Beukenlaan 88',
                    'adresregel2' => '3456GH Amersfoort',
                ],
            ],
        ],
    ],
];
