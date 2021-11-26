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
    public function run(): void
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
     * @return string
     */
    public function getRoleNameByKey(string $key): string
    {
        return [
            'admin'                                 => 'Beheerder',
            'finance'                               => 'Financien',
            'validation'                            => 'Validator',
            'policy_officer'                        => 'Manager',
            'operation_officer'                     => 'Kassa',
            'implementation_manager'                => 'Implementatie manager',
            'implementation_cms_manager'            => 'Implementatie CMS manager',
            'implementation_communication_manager'  => 'Implementatie communicatiemanager',
        ][$key] ?? $key;
    }
}
