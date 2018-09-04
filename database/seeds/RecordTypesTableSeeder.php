<?php

use \App\Services\Forus\Record\Models\RecordType;

class RecordTypesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RecordType::create([
            'key'       => 'primary_email',
            'name'      => 'Primary E-mail',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'email',
            'name'      => 'E-mail',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'given_name',
            'name'      => 'Given Name',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'family_name',
            'name'      => 'Family Name',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'children',
            'name'      => 'Children',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'children_nth',
            'name'      => 'Children count',
            'type'      => 'number',
        ]);

        RecordType::create([
            'key'       => 'parent',
            'name'      => 'Parent',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'address',
            'name'      => 'Address',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'birth_date',
            'name'      => 'Birth date',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'gender',
            'name'      => 'Gender',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'spouse',
            'name'      => 'Spouse',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'tax_id',
            'name'      => 'Tax ID',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'telephone',
            'name'      => 'Telephone',
            'type'      => 'string',
        ]);

        RecordType::create([
            'key'       => 'net_worth',
            'name'      => 'Net worth',
            'type'      => 'number',
        ]);

        RecordType::create([
            'key'       => 'base_salary',
            'name'      => 'Base salary',
            'type'      => 'number',
        ]);

        RecordType::create([
            'key'       => 'bsn',
            'name'      => 'BSN',
            'type'      => 'number',
        ]);

        RecordType::create([
            'key'       => 'kindpakket_2018_eligible',
            'name'      => 'Kindpakket Eligible',
            'type'      => 'string',
        ]);
    }
}
