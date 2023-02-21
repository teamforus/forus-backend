<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    protected array $roles = [
        'admin' => [
            'en' => 'Admin',
            'nl' => 'Beheerder',
        ],
        'finance' => [
            'en' => 'Finance manager',
            'nl' => 'Financieel manager',
        ],
        'validation' => [
            'en' => 'Validator',
            'nl' => 'Validator',
        ],
        'supervisor_validation' => [
            'en' => 'Supervisor validator',
            'nl' => 'Supervisor validator',
        ],
        'policy_officer' => [
            'en' => 'Manager',
            'nl' => 'Manager',
        ],
        'operation_officer' => [
            'en' => 'Kassa',
            'nl' => 'Kassa',
        ],
        'implementation_manager' => [
            'en' => 'Implementatie manager',
            'nl' => 'Implementatie manager',
        ],
        'implementation_cms_manager' => [
            'en' => 'Implementation CMS manager',
            'nl' => 'Implementatie CMS manager',
        ],
        'implementation_communication_manager' => [
            'en' => 'Implementatie communicatiemanager',
            'nl' => 'Implementatie communicatiemanager',
        ],
        'finance_reader' => [
            'en' => 'Finance reader',
            'nl' => 'Financieel analist',
        ],
        'bank_manager' => [
            'en' => 'Finance admin',
            'nl' => 'Financieel beheerder',
        ],
        'voucher_officer' => [
            'en' => 'Voucher admin',
            'nl' => 'Voucher beheerder',
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @param bool $withTranslations
     * @return void
     */
    public function run(bool $withTranslations = true): void
    {
        foreach ($this->roles as $key => $translations) {
            $role = Role::firstOrCreate(compact('key'));

            foreach ($withTranslations ? $translations : [] as $locale => $name) {
                $role->translation()->firstOrCreate(compact('locale'), compact('name'));
            }
        }
    }
}
