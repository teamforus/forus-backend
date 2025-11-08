<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    protected array $permissions = [
        Permission::MANAGE_ORGANIZATION => 'Manage organization',
        Permission::MANAGE_FUNDS => 'Manage funds',
        Permission::MANAGE_FUND_TEXTS => 'Manage funds texts',
        Permission::MANAGE_PROVIDERS => 'Manage providers',
        Permission::MANAGE_PROVIDER_FUNDS => 'Manage providers funds',
        Permission::MANAGE_PRODUCTS => 'Manage products',
        Permission::MANAGE_OFFICES => 'Manage offices',
        Permission::MANAGE_VALIDATORS => 'Manage validators',
        Permission::MANAGE_EMPLOYEES => 'Manage employees',
        Permission::MANAGE_VOUCHERS => 'Manage vouchers',
        Permission::VIEW_VOUCHERS => 'View vouchers',
        Permission::VIEW_IMPLEMENTATIONS => 'View implementations',
        Permission::MANAGE_IMPLEMENTATION => 'Manage implementation',
        Permission::MANAGE_IMPLEMENTATION_CMS => 'Manage implementation CMS',
        Permission::MANAGE_IMPLEMENTATION_NOTIFICATIONS => 'Manage implementation notifications',
        Permission::MANAGE_BANK_CONNECTIONS => 'Manage bank connections',
        Permission::MANAGE_TRANSACTION_BULKS => 'Manage transaction bulks',
        Permission::MANAGE_REIMBURSEMENTS => 'Manage reimbursements',
        Permission::VIEW_FINANCES => 'See financial overview',
        Permission::VALIDATE_RECORDS => 'Validate records',
        Permission::SCAN_VOUCHERS => 'Scan vouchers',
        Permission::VIEW_FUNDS => 'See funds overview',
        Permission::VIEW_PERSON_BSN_DATA => 'See person information by BSN',
        Permission::MAKE_DIRECT_PAYMENTS => 'Make direct payments',
        Permission::MANAGE_BI_CONNECTION => 'Manage BI connection',
        Permission::MANAGE_PAYMENT_METHODS => 'Manage payment methods',
        Permission::VIEW_FUNDS_EXTRA_PAYMENTS => 'See funds extra payments',
        Permission::MANAGE_PAYOUTS => 'Manage payouts',
        Permission::VIEW_IDENTITIES => 'View identities',
        Permission::MANAGE_IDENTITIES => 'Manage identities',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->permissions as $key => $name) {
            Permission::updateOrCreate(compact('key'), compact('name'));
        }
    }
}
