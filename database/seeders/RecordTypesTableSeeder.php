<?php

namespace Database\Seeders;

use App\Enums\RecordValidationKey;
use App\Helpers\Arr;
use App\Models\ProfileRecordGroup;
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
        'criteria' => true,
        'profile_records' => true,
        'name' => 'Voornaam',
        'vouchers' => true,
        'requester_imports' => true,
        'validation_key' => RecordValidationKey::PERSON_NAME,
    ], [
        'key' => 'family_name',
        'criteria' => true,
        'profile_records' => true,
        'name' => 'Achternaam',
        'vouchers' => true,
        'requester_imports' => true,
        'validation_key' => RecordValidationKey::PERSON_NAME,
    ], [
        'key' => 'children_nth',
        'profile_records' => true,
        'requester_imports' => true,
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
        'profile_records' => true,
        'name' => 'Geboortedatum',
        'type' => RecordType::TYPE_DATE,
        'vouchers' => true,
        'criteria' => true,
        'requester_imports' => true,
        'validation_key' => RecordValidationKey::BIRTH_DATE,
    ], [
        'key' => 'spouse',
        'name' => 'Spouse',
    ], [
        'key' => 'tax_id',
        'name' => 'Tax ID',
        'control_type' => RecordType::CONTROL_TYPE_NUMBER,
    ], [
        'key' => 'telephone',
        'validation_key' => RecordValidationKey::SHORT_TEXT,
        'profile_records' => true,
        'profile_requester_editable' => true,
        'name' => 'Vast telefoonnummer',
        'vouchers' => true,
        'criteria' => true,
    ], [
        'key' => 'net_worth',
        'profile_records' => true,
        'requester_imports' => true,
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
        'validation_key' => RecordValidationKey::SHORT_TEXT,
        'profile_records' => true,
        'profile_requester_editable' => true,
        'name' => 'Mobiele telefoonnummer',
        'system' => true,
    ], [
        'key' => 'city',
        'criteria' => true,
        'validation_key' => RecordValidationKey::CITY_NAME,
        'profile_records' => true,
        'requester_imports' => true,
        'profile_requester_editable' => true,
        'name' => 'Woonplaats',
        'system' => true,
    ], [
        'key' => 'street',
        'criteria' => true,
        'validation_key' => RecordValidationKey::STREET_NAME,
        'profile_records' => true,
        'requester_imports' => true,
        'profile_requester_editable' => true,
        'name' => 'Straatnaam',
        'system' => true,
    ], [
        'key' => 'house_number',
        'criteria' => true,
        'validation_key' => RecordValidationKey::HOUSE_NUMBER,
        'profile_records' => true,
        'requester_imports' => true,
        'profile_requester_editable' => true,
        'name' => 'Huisnummer',
        'system' => true,
    ], [
        'key' => 'house_number_addition',
        'criteria' => true,
        'validation_key' => RecordValidationKey::HOUSE_ADDITION,
        'profile_records' => true,
        'requester_imports' => true,
        'profile_requester_editable' => true,
        'name' => 'Huisnummer toevoeging',
        'system' => true,
    ], [
        'key' => 'postal_code',
        'criteria' => true,
        'validation_key' => RecordValidationKey::POSTCODE,
        'profile_records' => true,
        'requester_imports' => true,
        'profile_requester_editable' => true,
        'name' => 'Postcode',
        'system' => true,
    ], [
        'key' => 'house_composition',
        'profile_records' => true,
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
        'profile_records' => true,
        'requester_imports' => true,
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
        'profile_records' => true,
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
        'profile_records' => true,
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
        'validation_key' => RecordValidationKey::SHORT_TEXT,
        'profile_records' => true,
        'requester_imports' => true,
        'name' => 'Klantnummer',
        'system' => true,
    ], [
        'key' => 'neighborhood_name',
        'validation_key' => RecordValidationKey::SHORT_TEXT,
        'profile_records' => true,
        'name' => 'Woonwijk',
        'system' => true,
    ], [
        'key' => 'municipality_name',
        'validation_key' => RecordValidationKey::SHORT_TEXT,
        'profile_records' => true,
        'name' => 'Gemeentenaam',
        'system' => true,
    ], [
        'key' => 'initials',
        'name' => 'Initials',
        'validation_key' => RecordValidationKey::NAME_PART,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'family_name_prefix',
        'name' => 'Last name prefix',
        'validation_key' => RecordValidationKey::NAME_PART,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'death_date',
        'name' => 'Date of death',
        'type' => RecordType::TYPE_DATE,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'reference_person_bsn',
        'name' => 'Reference person BSN',
        'validation_key' => RecordValidationKey::BSN,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'household_role',
        'name' => 'Household role',
        'type' => RecordType::TYPE_SELECT,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
        'options' => [
            ['value' => 'head', 'name' => 'Head of household'],
            ['value' => 'partner', 'name' => 'Partner'],
            ['value' => 'child', 'name' => 'Child'],
        ],
    ], [
        'key' => 'companion_pass',
        'name' => 'Companion pass',
        'type' => RecordType::TYPE_BOOL,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'has_pet',
        'name' => 'Has a pet',
        'type' => RecordType::TYPE_BOOL,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'municipality_code',
        'name' => 'Municipality code',
        'type' => RecordType::TYPE_SELECT,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'house_letter',
        'name' => 'House letter',
        'validation_key' => RecordValidationKey::HOUSE_LETTER,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_street',
        'name' => 'Mailing street name',
        'validation_key' => RecordValidationKey::STREET_NAME,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_house_number',
        'name' => 'Mailing house number',
        'validation_key' => RecordValidationKey::HOUSE_NUMBER,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_house_letter',
        'name' => 'Mailing house letter',
        'validation_key' => RecordValidationKey::HOUSE_LETTER,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_house_number_addition',
        'name' => 'Mailing house number addition',
        'validation_key' => RecordValidationKey::HOUSE_ADDITION,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_postal_code',
        'name' => 'Mailing postal code',
        'validation_key' => RecordValidationKey::POSTCODE,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ], [
        'key' => 'mailing_city',
        'name' => 'Mailing city',
        'validation_key' => RecordValidationKey::CITY_NAME,
        'profile_records' => true,
        'requester_imports' => true,
        'criteria' => true,
    ]];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $groups = ProfileRecordGroup::query()->pluck('id', 'key');
        $assignments = [];

        foreach ([
            'personal' => [
                'given_name', 'family_name', 'birth_date', 'gender', 'marital_status', 'client_number',
            ],
            'house' => [
                'house_composition', 'living_arrangement',
            ],
            'contacts' => [
                'telephone', 'mobile',
            ],
            'address' => [
                'city', 'street', 'house_number', 'house_number_addition', 'postal_code',
                'neighborhood_name', 'municipality_name',
            ],
            'mailing_address' => [
                'mailing_street', 'mailing_house_number', 'mailing_house_letter', 'mailing_house_number_addition',
                'mailing_postal_code', 'mailing_city',
            ],
            'household_relationships' => [
                'reference_person_bsn', 'household_role',
            ],
        ] as $group => $keys) {
            foreach ($keys as $index => $key) {
                $assignments[$key] = [
                    'profile_record_group_id' => $groups->get($group),
                    'profile_sort_order' => ($index + 1) * 20,
                ];
            }
        }

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
                ...$assignments[$type['key']] ?? [],
                ...Arr::only($type, [
                    'key', 'name', 'type', 'required', 'criteria', 'system', 'vouchers', 'control_type',
                    'requester_imports', 'profile_records', 'profile_requester_editable', 'validation_key',
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
