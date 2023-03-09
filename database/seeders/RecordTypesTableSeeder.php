<?php

namespace Database\Seeders;

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
    ], [
        'key' => 'given_name',
        'name' => 'Given Name',
        'vouchers' => true,
    ], [
        'key' => 'family_name',
        'name' => 'Family Name',
        'vouchers' => true,
    ], [
        'key' => 'children',
        'name' => 'Children',
    ], [
        'key' => 'children_nth',
        'name' => 'Children count',
        'type' => 'number',
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
        'vouchers' => true,
    ], [
        'key' => 'gender',
        'name' => 'Gender',
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
    ], [
        'key' => 'bsn',
        'name' => 'BSN',
        'type' => 'number',
        'system' => true,
    ], [
        'key' => 'kindpakket_2018_eligible',
        'name' => 'Kindpakket Eligible',
        'system' => true,
    ], [
        'key' => 'uid',
        'name' => 'UID',
        'system' => true,
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
        foreach ($this->recordTypes as $recordType) {
            RecordType::create(array_merge([
                'type' => 'string',
                'system' => false,
                'vouchers' => false,
            ], $recordType));
        }
    }
}
