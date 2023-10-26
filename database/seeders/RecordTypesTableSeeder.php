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
        'name' => 'Given Name',
        'vouchers' => true,
    ], [
        'key' => 'family_name',
        'name' => 'Family Name',
        'vouchers' => true,
    ], [
        'key' => 'children_nth',
        'name' => 'Number of children',
        'type' => 'number',
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
        'name' => 'Birth date',
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
    ], [
        'key' => 'telephone',
        'name' => 'Telephone',
        'vouchers' => true,
    ], [
        'key' => 'net_worth',
        'name' => 'Net worth',
        'type' => 'number',
    ], [
        'key' => 'base_salary',
        'name' => 'Base salary',
        'type' => 'number',
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
        'criterion' => true,
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
    ]];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->recordTypes as $type) {
            $recordType = RecordType::create([
                'type' => 'string',
                'system' => false,
                'vouchers' => false,
                'criteria' => false,
                ...Arr::only($type, [
                    'key', 'name', 'type', 'required', 'criteria', 'system', 'vouchers',
                ]),
            ]);

            $recordType->record_type_options()->createMany(array_map(fn ($option) => [
                'value' => $option[0],
                'name' => $option[1],
            ], $type['options'] ?? []));
        }
    }
}
