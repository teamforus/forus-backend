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
            'nl' => 'Financieel medewerker',
        ],
        'validation' => [
            'en' => 'Validator',
            'nl' => 'Beoordelaar',
        ],
        'supervisor_validation' => [
            'en' => 'Supervisor validator',
            'nl' => 'Manager beoordelaars',
        ],
        'policy_officer' => [
            'en' => 'Manager',
            'nl' => 'Manager aanbieders',
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
            'nl' => 'CMS manager',
        ],
        'implementation_communication_manager' => [
            'en' => 'Implementatie communicatiemanager',
            'nl' => 'Communicatie',
        ],
        'finance_reader' => [
            'en' => 'Finance reader',
            'nl' => 'Financieel raadpleger',
        ],
        'bank_manager' => [
            'en' => 'Bank manager',
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
        foreach ($this->getRoles() as $key => $translations) {
            $role = Role::firstOrCreate(compact('key'));

            foreach ($withTranslations ? $translations : [] as $locale => $name) {
                $role->translations()->updateOrCreate(compact('locale'), compact('name'));
            }
        }
    }

    /**
     * @return array|\string[][]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
