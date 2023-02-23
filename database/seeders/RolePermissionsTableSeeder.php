<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RolePermission;
use App\Models\Permission;
use App\Models\Role;

class RolePermissionsTableSeeder extends Seeder
{
    /**
     * @var string[][]
     */
    protected array $rolePermissions = [
        "admin" => [
            "manage_funds", "manage_providers", "manage_products", "manage_offices",
            "view_finances", "validate_records", "scan_vouchers", "manage_provider_funds",
            "manage_vouchers", "manage_employees", "manage_organization",
            "manage_implementation", "manage_implementation_cms",
            "manage_bank_connections", "manage_transaction_bulks",
            "manage_reimbursements", "manage_implementation_notifications",
            "view_funds", "view_person_bsn_data", "make_direct_payments", 'manage_fund_texts',
        ],
        "finance" => [
            "view_finances", "manage_vouchers",
            "manage_reimbursements", "manage_organization", "manage_funds", "manage_transaction_bulks",
        ],
        "validation" => [
            "validate_records", "view_funds", "view_person_bsn_data"
        ],
        "supervisor_validation" => [
            "manage_validators", "view_funds",
        ],
        "policy_officer" => [
            "view_funds", 'manage_providers', 'manage_products',
            "manage_offices", "manage_provider_funds",
        ],
        "operation_officer" => [
            "scan_vouchers",
        ],
        "voucher_officer" => [
            "manage_funds", "manage_vouchers", "view_person_bsn_data"
        ],
        "implementation_manager" => [
            "view_funds", "manage_implementation", "manage_implementation_cms",
        ],
        "implementation_cms_manager" => [
            "view_funds", "manage_implementation_cms",
        ],
        "implementation_communication_manager" => [
            "view_funds", "manage_implementation_cms", "manage_implementation_notifications", "manage_fund_texts"
        ],
        "bank_manager" => [
            "manage_bank_connections", "manage_transaction_bulks", "view_funds",
        ],
        "finance_reader" => [
            "view_finances",
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $roles = Role::pluck('id','key');
        $permissions = Permission::pluck('id','key');
      
        foreach ($this->rolePermissions as $roleKey => $permissionKeys) {
            foreach ($permissionKeys as $permissionKey) {
                RolePermission::create([
                    'role_id' => $roles[$roleKey],
                    'permission_id' => $permissions[$permissionKey],
                ]);
            }
        }
    }
}
