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
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rolePermissions = [
            "admin" => [
                "manage_funds", "manage_providers", "manage_products",
                "manage_offices", "view_finances", "validate_records",
                "scan_vouchers", "manage_provider_funds", "manage_vouchers",
            ],
            "finance" => [
                "view_finances", "manage_vouchers",
            ],
            "validation" => [
                "validate_records", "view_funds"
            ],
            "policy_officer" => [
                "manage_funds", 'manage_providers', 'manage_products',
                "manage_offices", "manage_provider_funds",
            ],
            "operation_officer" => [
                "scan_vouchers"
            ]
        ];

        $permissions = Permission::query()->pluck('id','key');
        $roles = Role::query()->pluck('id','key');

        collect($rolePermissions)->each(function($permissionKeys, $roleKey) use (
            $permissions, $roles
        ) {
            collect($permissionKeys)->each(function($permissionKey) use (
                $permissions, $roles, $roleKey
            ) {
                RolePermission::create([
                    'role_id' => $roles[$roleKey],
                    'permission_id' => $permissions[$permissionKey],
                ]);
            });
        });
    }
}
