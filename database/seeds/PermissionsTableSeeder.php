<?php

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'manage_organization'   => "Manage organization",
            'manage_funds'          => "Manage funds",
            'manage_providers'      => "Manage providers",
            'manage_provider_funds' => "Manage providers funds",
            'manage_products'       => "Manage products",
            'manage_offices'        => "Manage offices",
            'manage_validators'     => "Manage validators",
            'manage_employees'      => "Manage employees",
            'view_finances'         => "See financial overview",
            'validate_records'      => "Validate records",
            'scan_vouchers'         => "Scan vouchers",
        ];

        collect($permissions)->each(function($name, $key) {
            Permission::create(compact('name', 'key'));
        });
    }
}
