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
        'supervisor_validator' => [
            'en' => 'Supervisor validator',
            'nl' => 'Toezichthouder beoordelaar',
        ],
        'policy_officer' => [
            'en' => 'Providers manager',
            'nl' => 'Manager aanbieders',
        ],
        'operation_officer' => [
            'en' => 'Operation manager',
            'nl' => 'Kassa',
        ],
        'implementation_communication_manager' => [
            'en' => 'Implementation communication manager',
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
            'nl' => 'Tegoed beheerder',
        ],
        'payouts_manager' => [
            'en' => 'Payouts manage',
            'nl' => 'Uitbetalingen beheerder',
        ],
        'voucher_reader' => [
            'en' => 'Voucher viewer',
            'nl' => 'Raadpleger',
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
