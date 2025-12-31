<?php

namespace Database\Seeders;

use App\Helpers\Arr;
use App\Models\RecordType;

class RecordTypesTableSeeder extends DatabaseSeeder
{
    protected array $recordTypes = [[
        'key' => 'primary_email',
        'name' => 'Primary E-mail',
        'system' => true,
    ], [
        'key' => 'email',
        'name' => 'E-mail',
        'type' => RecordType::TYPE_EMAIL,
        'vouchers' => true,
        'criteria' => true,
    ], [
        'key' => 'given_name',
        'name' => 'Voornaam',
        'vouchers' => true,
    ], [
        'key' => 'family_name',
        'name' => 'Achternaam',
        'vouchers' => true,
    ], [
        'key' => 'children_nth',
        'name' => 'Number of children',
        'type' => RecordType::TYPE_NUMBER,
        'control_type' => RecordType::CONTROL_TYPE_STEP,
        'vouchers' => true,
        'criteria' => true,
    ], [
        'key' => 'parent',
        'name' => 'Parent',
    ], [
        'key' => 'address',
        'name' => 'Address',
        'vouchers' => true,
    ], [
        'key' => 'birth_date',
        'name' => 'Geboortedatum',
        'type' => RecordType::TYPE_DATE,
        'vouchers' => true,
        'criteria' => true,
    ], [
        'key' => 'spouse',
        'name' => 'Spouse',
    ], [
        'key' => 'tax_id',
        'name' => 'Tax ID',
        'control_type' => RecordType::CONTROL_TYPE_NUMBER,
    ], [
        'key' => 'telephone',
        'name' => 'Vast telefoonnummer',
        'vouchers' => true,
    ], [
        'key' => 'net_worth',
        'name' => 'Net worth',
        'type' => RecordType::TYPE_NUMBER,
        'control_type' => RecordType::CONTROL_TYPE_CURRENCY,
        'criteria' => true,
    ], [
        'key' => 'base_salary',
        'name' => 'Base salary',
        'type' => RecordType::TYPE_NUMBER,
        'control_type' => RecordType::CONTROL_TYPE_CURRENCY,
        'criteria' => true,
    ], [
        'key' => 'bsn',
        'name' => 'BSN',
        'type' => RecordType::TYPE_NUMBER,
        'system' => true,
    ], [
        'key' => 'uid',
        'name' => 'UID',
        'system' => true,
        'criteria' => true,
    ], [
        'key' => 'partner_bsn',
        'name' => 'Partner BSN',
        'type' => RecordType::TYPE_NUMBER,
        'system' => true,
    ], [
        'key' => 'mobile',
        'name' => 'Mobiele telefoonnummer',
        'system' => true,
    ], [
        'key' => 'city',
        'name' => 'Woonplaats',
        'system' => true,
    ], [
        'key' => 'street',
        'name' => 'Straatnaam',
        'system' => true,
    ], [
        'key' => 'house_number',
        'name' => 'Huisnummer',
        'system' => true,
    ], [
        'key' => 'house_number_addition',
        'name' => 'Huisnummer toevoeging',
        'system' => true,
    ], [
        'key' => 'postal_code',
        'name' => 'Postcode',
        'system' => true,
    ], [
        'key' => 'house_composition',
        'name' => 'Gezinssamenstelling',
        'system' => true,
        'type' => RecordType::TYPE_SELECT,
        'options' => [
            ['value' => 'onbekend', 'name' => 'Onbekend'],
            ['value' => 'alleenstaand', 'name' => 'Alleenstaand'],
            ['value' => 'samenwonend zonder kinderen', 'name' => 'Samenwonend zonder kinderen'],
            ['value' => 'samenwonend met kinderen', 'name' => 'Samenwonend met kinderen'],
            ['value' => 'eenoudergezin', 'name' => 'Eenoudergezin'],
        ],
    ], [
        'key' => 'gender',
        'name' => 'Geslacht',
        'system' => true,
        'type' => RecordType::TYPE_SELECT,
        'criteria' => true,
        'options' => [
            ['value' => 'onbekend', 'name' => 'Onbekend'],
            ['value' => 'mannelijk', 'name' => 'Mannelijk'],
            ['value' => 'vrouwelijk', 'name' => 'Vrouwelijk'],
            ['value' => 'niet gespecificeerd', 'name' => 'Niet gespecificeerd'],
        ],
    ], [
        'key' => 'living_arrangement',
        'name' => 'Leefvorm',
        'type' => RecordType::TYPE_SELECT,
        'system' => true,
        'options' => [
            [
                'value' => 'onbekend',
                'name' => 'Onbekend',
            ],
            [
                'value' => 'alleenstaande',
                'name' => 'Alleenstaande',
            ],
            [
                'value' => 'eenoudergezin',
                'name' => 'Eenoudergezin',
            ],
            [
                'value' => 'samenwonend met partner met samenlevingscontract',
                'name' => 'Samenwonend met partner met samenlevingscontract',
            ],
            [
                'value' => 'samenwonend met partner zonder samenlevingscontract',
                'name' => 'Samenwonend met partner zonder samenlevingscontract',
            ],
            [
                'value' => 'samenwonend met inkomensafhankelijke kinderen',
                'name' => 'Samenwonend met inkomensafhankelijke kinderen',
            ],
            [
                'value' => 'samenwonend met (een) andere alleenstaande(n)',
                'name' => 'Samenwonend met (een) andere alleenstaande(n)',
            ],
            [
                'value' => 'samenwonend met huwelijks- of geregistreerd partner',
                'name' => 'Samenwonend met huwelijks- of geregistreerd partner',
            ],
            [
                'value' => 'gehuwd/ongehuwd samenwonend',
                'name' => 'Gehuwd/ongehuwd samenwonend',
            ],
            [
                'value' => 'anders',
                'name' => 'Anders',
            ],
        ],
    ], [
        'key' => 'marital_status',
        'name' => 'Burgerlijke Staat',
        'type' => RecordType::TYPE_SELECT,
        'system' => true,
        'options' => [
            [
                'value' => 'onbekend',
                'name' => 'Onbekend',
            ],
            [
                'value' => 'ongehuwd en geen geregistreerd partner en nooit gehuwd of geregistreerd partner geweest',
                'name' => 'Ongehuwd en geen geregistreerd partner en nooit gehuwd of geregistreerd partner geweest',
            ],
            [
                'value' => 'gehuwd',
                'name' => 'Gehuwd',
            ],
            [
                'value' => 'gescheiden',
                'name' => 'Gescheiden',
            ],
            [
                'value' => 'weduwe/weduwnaar',
                'name' => 'Weduwe/weduwnaar',
            ],
            [
                'value' => 'geregistreerd partner',
                'name' => 'Geregistreerd partner',
            ],
            [
                'value' => 'gescheiden geregistreerd partner',
                'name' => 'Gescheiden geregistreerd partner',
            ],
            [
                'value' => 'achtergebleven geregistreerd partner',
                'name' => 'Achtergebleven geregistreerd partner',
            ],
            [
                'value' => 'ongehuwd en geen geregistreerd partner, eventueel wel gehuwd of geregistreerd partner geweest',
                'name' => 'Ongehuwd en geen geregistreerd partner, eventueel wel gehuwd of geregistreerd partner geweest',
            ],
        ],
    ], [
        'key' => 'client_number',
        'name' => 'Klantnummer',
        'system' => true,
    ], [
        'key' => 'neighborhood_name',
        'name' => 'Woonwijk',
        'system' => true,
    ], [
        'key' => 'municipality_name',
        'name' => 'Gemeentenaam',
        'system' => true,
    ]];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $baseTypes = [
            RecordType::TYPE_BOOL => RecordType::CONTROL_TYPE_CHECKBOX,
            RecordType::TYPE_DATE => RecordType::CONTROL_TYPE_DATE,
            RecordType::TYPE_STRING => RecordType::CONTROL_TYPE_TEXT,
            RecordType::TYPE_EMAIL => RecordType::CONTROL_TYPE_TEXT,
            'bsn' => RecordType::CONTROL_TYPE_NUMBER,
            RecordType::TYPE_IBAN => RecordType::CONTROL_TYPE_TEXT,
            RecordType::TYPE_NUMBER => RecordType::CONTROL_TYPE_NUMBER,
            RecordType::TYPE_SELECT => RecordType::CONTROL_TYPE_SELECT,
            RecordType::TYPE_SELECT_NUMBER => RecordType::CONTROL_TYPE_SELECT,
        ];

        foreach ($this->recordTypes as $type) {
            $recordType = RecordType::create([
                'type' => RecordType::TYPE_STRING,
                'system' => false,
                'vouchers' => false,
                'criteria' => false,
                'control_type' => $baseTypes[$type['type'] ?? null] ?? RecordType::CONTROL_TYPE_TEXT,
                ...Arr::only($type, [
                    'key', 'name', 'type', 'required', 'criteria', 'system', 'vouchers', 'control_type',
                ]),
            ]);

            foreach ($type['options'] ?? [] as $option) {
                $recordType
                    ->record_type_options()
                    ->create(Arr::only($option, ['value']))
                    ->translateOrNew('nl')
                    ->fill(Arr::only($option, ['name']))
                    ->save();
            }
        }
    }
}
