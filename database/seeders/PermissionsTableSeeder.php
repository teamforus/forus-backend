<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    protected array $permissions = [
        'manage_organization'       => "Manage organization",
        'manage_funds'              => "Manage funds",
        'manage_providers'          => "Manage providers",
        'manage_provider_funds'     => "Manage providers funds",
        'manage_products'           => "Manage products",
        'manage_offices'            => "Manage offices",
        'manage_validators'         => "Manage validators",
        'manage_employees'          => "Manage employees",
        'manage_vouchers'           => "Manage vouchers",
        'manage_implementation'     => "Manage implementation",
        'manage_implementation_cms' => "Manage implementation CMS",
        'manage_implementation_notifications' => "Manage implementation notifications",
        'manage_bank_connections'   => "Manage bank connections",
        'manage_transaction_bulks'  => "Manage transaction bulks",
        'manage_reimbursements'     => "Manage reimbursements",
        'view_finances'             => "See financial overview",
        'validate_records'          => "Validate records",
        'scan_vouchers'             => "Scan vouchers",
        'view_funds'                => "See funds overview",
        'view_person_bsn_data'      => "See person information by BSN",
        'make_direct_payments'      => "Make direct payments",
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->permissions as $key => $name) {
            Permission::create(compact('name', 'key'));
        }
    }
}
