<?php

namespace Database\Seeders;

use App\Services\Forus\Record\Models\RecordType;

class RecordTypesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        RecordType::create([
            'key'       => 'primary_email',
            'name'      => 'Primary E-mail',
            'type'      => 'string',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'email',
            'name'      => 'E-mail',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'given_name',
            'name'      => 'Given Name',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'family_name',
            'name'      => 'Family Name',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'children',
            'name'      => 'Children',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'children_nth',
            'name'      => 'Children count',
            'type'      => 'number',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'parent',
            'name'      => 'Parent',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'address',
            'name'      => 'Address',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'birth_date',
            'name'      => 'Birth date',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'gender',
            'name'      => 'Gender',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'spouse',
            'name'      => 'Spouse',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'tax_id',
            'name'      => 'Tax ID',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'telephone',
            'name'      => 'Telephone',
            'type'      => 'string',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'net_worth',
            'name'      => 'Net worth',
            'type'      => 'number',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'base_salary',
            'name'      => 'Base salary',
            'type'      => 'number',
            'system'    => false,
        ]);

        RecordType::create([
            'key'       => 'bsn',
            'name'      => 'BSN',
            'type'      => 'number',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'kindpakket_2018_eligible',
            'name'      => 'Kindpakket Eligible',
            'type'      => 'string',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'uid',
            'name'      => 'UID',
            'type'      => 'string',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'bsn_hash',
            'name'      => 'BSN Hash',
            'type'      => 'string',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'partner_bsn',
            'name'      => 'Partner BSN',
            'type'      => 'number',
            'system'    => true,
        ]);

        RecordType::create([
            'key'       => 'partner_bsn_hash',
            'name'      => 'Partner BSN Hash',
            'type'      => 'string',
            'system'    => true,
        ]);
    }
}
