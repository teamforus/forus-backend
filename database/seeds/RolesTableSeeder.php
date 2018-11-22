<?php

use Illuminate\Database\Seeder;
use App\Models\Role;

/**
 * Class RolesTableSeeder
 */
class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'key' => 'admin',
            'name' => 'Admin',
        ]);

        Role::create([
            'key' => 'finance',
            'name' => 'Finance',
        ]);

        Role::create([
            'key' => 'validation',
            'name' => 'Validation',
        ]);

        Role::create([
            'key' => 'policy_officer',
            'name' => 'Policy officer',
        ]);

        Role::create([
            'key' => 'operation_officer',
            'name' => 'Operation officer',
        ]);
    }
}
