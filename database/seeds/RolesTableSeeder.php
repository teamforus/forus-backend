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
    public function run(): void
    {
        Role::create([
            'key' => 'admin',
            // 'name' => 'Beheerder',
        ]);

        Role::create([
            'key' => 'finance',
            // 'name' => 'Financien',
        ]);

        Role::create([
            'key' => 'validation',
            // 'name' => 'Validator',
        ]);

        Role::create([
            'key' => 'policy_officer',
            // 'name' => 'Manager',
        ]);

        Role::create([
            'key' => 'operation_officer',
            // 'name' => 'Kassa',
        ]);

        Role::create([
            'key' => 'implementation_manager',
            // 'name' => 'Implementatie manager',
        ]);

        Role::create([
            'key' => 'implementation_cms_manager',
            // 'name' => 'Implementatie CMS manager',
        ]);

        Role::create([
            'key' => 'implementation_communication_manager',
            // 'name' => 'Implementatie CMS manager',
        ]);
    }
}
