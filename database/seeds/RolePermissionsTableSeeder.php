<?php

use Illuminate\Database\Seeder;
use App\Models\RolePermission;
use App\Models\Permission;
use App\Models\Role;

/**
 * Class RolePermissionsTableSeeder
 */
class RolePermissionsTableSeeder extends Seeder
{
    /**
     * @var string[][]
     */
    protected $rolePermissions = [
        "admin" => [
            "manage_funds", "manage_providers", "manage_products", "manage_offices",
            "view_finances", "validate_records", "scan_vouchers", "manage_provider_funds",
            "manage_vouchers", "manage_employees", "manage_organization",
            "manage_implementation", "manage_implementation_cms",
            "manage_bank_connections", "manage_transaction_bulks",
        ],
        "finance" => [
            "view_finances", "manage_vouchers",
        ],
        "validation" => [
            "validate_records", "view_funds",
        ],
        "policy_officer" => [
            "manage_funds", 'manage_providers', 'manage_products',
            "manage_offices", "manage_provider_funds",
        ],
        "operation_officer" => [
            "scan_vouchers",
        ],
        "implementation_manager" => [
            "view_funds", "manage_implementation", "manage_implementation_cms",
        ],
        "implementation_cms_manager" => [
            "view_funds", "manage_implementation_cms",
        ],
        "implementation_communication_manager" => [
            "view_funds", "manage_implementation_cms", "manage_implementation_notifications"
        ]
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
