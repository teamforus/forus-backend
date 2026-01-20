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

return [
    'default' => env('PERSON_BSN_API_SERVICE', 'iconnect'),
    'fund_prefill_cache_time' => env('PERSON_BSN_FUND_PREFILL_CACHE_TIME', 60 * 15),
    'test_response' => env('PERSON_BSN_TEST_RESPONSE', false),

    'test_response_data' => [
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
    ],
];
