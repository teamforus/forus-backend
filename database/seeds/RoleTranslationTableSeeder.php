<?php

use Illuminate\Database\Seeder;

use App\Models\Role;

class RoleTranslationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = Role::get();

        foreach ($roles as $role) {
            $role->translations()->create([
                'name'      => $this->getRoleNameByKey($role->key),
                'locale'    => 'nl'
            ]);
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getRoleNameByKey(string $key) {
        return [
            'admin'             => 'Beheerder',
            'finance'           => 'Financien',
            'validation'        => 'Validator',
            'policy_officer'    => 'Manager',
            'operation_officer' => 'Kassa',
            'implementation_manager' => 'Implementatie manager',
        ][$key];
    }
}
