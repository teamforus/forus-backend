<?php

namespace Database\Seeders;

use App\Models\ProfileRecordGroup;
use Illuminate\Database\Seeder;

class ProfileRecordGroupsTableSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        foreach ([
            ['key' => 'personal', 'name' => 'Persoonsgegevens', 'sort_order' => 20],
            ['key' => 'house', 'name' => 'Huishouden', 'sort_order' => 40],
            ['key' => 'contacts', 'name' => 'Contactgegevens', 'sort_order' => 60],
            ['key' => 'address', 'name' => 'Adresgegevens', 'sort_order' => 80],
            ['key' => 'mailing_address', 'name' => 'Mailing address', 'sort_order' => 100],
            ['key' => 'household_relationships', 'name' => 'Household relationships', 'sort_order' => 120],
        ] as $group) {
            $model = ProfileRecordGroup::create([
                'key' => $group['key'],
                'sort_order' => $group['sort_order'],
            ]);

            $model->translateOrNew('nl')->fill([
                'name' => $group['name'],
            ])->save();
        }
    }
}
