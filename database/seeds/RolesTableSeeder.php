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
        ]);

        Role::create([
            'key' => 'finance',
        ]);

        Role::create([
            'key' => 'validation',
        ]);

        Role::create([
            'key' => 'policy_officer',
        ]);

        Role::create([
            'key' => 'operation_officer',
        ]);

        Role::create([
            'key' => 'implementation_manager',
        ]);
    }
}
