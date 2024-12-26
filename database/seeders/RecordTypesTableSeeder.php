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
        'type' => 'email',
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
        'type' => 'number',
        'control_type' => 'step',
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
        'type' => 'date',
        'vouchers' => true,
        'criteria' => true,
    ], [
        'key' => 'gender',
        'name' => 'Gender',
        'criteria' => true,
    ], [
        'key' => 'spouse',
        'name' => 'Spouse',
    ], [
        'key' => 'tax_id',
        'name' => 'Tax ID',
        'control_type' => 'number',
    ], [
        'key' => 'telephone',
        'name' => 'Vast telefoonnummer',
        'vouchers' => true,
    ], [
        'key' => 'net_worth',
        'name' => 'Net worth',
        'type' => 'number',
        'control_type' => 'currency',
        'criteria' => true,
    ], [
        'key' => 'base_salary',
        'name' => 'Base salary',
        'type' => 'number',
        'control_type' => 'currency',
        'criteria' => true,
    ], [
        'key' => 'bsn',
        'name' => 'BSN',
        'type' => 'number',
        'system' => true,
    ], [
        'key' => 'uid',
        'name' => 'UID',
        'system' => true,
        'criteria' => true,
    ], [
        'key' => 'bsn_hash',
        'name' => 'BSN Hash',
        'system' => true,
    ], [
        'key' => 'partner_bsn',
        'name' => 'Partner BSN',
        'type' => 'number',
        'system' => true,
    ], [
        'key' => 'partner_bsn_hash',
        'name' => 'Partner BSN Hash',
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
    ]];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $baseTypes = [
            'bool' => 'checkbox',
            'date' => 'date',
            'string' => 'text',
            'email' => 'text',
            'bsn' => 'number',
            'iban' => 'text',
            'number' => 'number',
            'select' => 'select',
            'select_number' => 'select',
        ];

        foreach ($this->recordTypes as $type) {
            $recordType = RecordType::create([
                'type' => 'string',
                'system' => false,
                'vouchers' => false,
                'criteria' => false,
                'control_type' => $baseTypes[$type['type'] ?? null] ?? 'text',
                ...Arr::only($type, [
                    'key', 'name', 'type', 'required', 'criteria', 'system', 'vouchers', 'control_type',
                ]),
            ]);

            $recordType->record_type_options()->createMany(array_map(fn ($option) => [
                'value' => $option[0],
                'name' => $option[1],
            ], $type['options'] ?? []));
        }
    }
}
