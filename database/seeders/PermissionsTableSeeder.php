<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    protected array $permissions = [
        'manage_organization' => 'Manage organization',
        'manage_funds' => 'Manage funds',
        'manage_fund_texts' => 'Manage funds texts',
        'manage_providers' => 'Manage providers',
        'manage_provider_funds' => 'Manage providers funds',
        'manage_products' => 'Manage products',
        'manage_offices' => 'Manage offices',
        'manage_validators' => 'Manage validators',
        'manage_employees' => 'Manage employees',
        'manage_vouchers' => 'Manage vouchers',
        'view_vouchers' => 'View vouchers',
        'view_implementations' => 'View implementations',
        'manage_implementation' => 'Manage implementation',
        'manage_implementation_cms' => 'Manage implementation CMS',
        'manage_implementation_notifications' => 'Manage implementation notifications',
        'manage_bank_connections' => 'Manage bank connections',
        'manage_transaction_bulks' => 'Manage transaction bulks',
        'manage_reimbursements' => 'Manage reimbursements',
        'view_finances' => 'See financial overview',
        'validate_records' => 'Validate records',
        'scan_vouchers' => 'Scan vouchers',
        'view_funds' => 'See funds overview',
        'view_person_bsn_data' => 'See person information by BSN',
        'make_direct_payments' => 'Make direct payments',
        'manage_bi_connection' => 'Manage BI connection',
        'manage_payment_methods' => 'Manage payment methods',
        'view_funds_extra_payments' => 'See funds extra payments',
        'manage_payouts' => 'Manage payouts',
        'view_identities' => 'View identities',
        'manage_identities' => 'Manage identities',
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
