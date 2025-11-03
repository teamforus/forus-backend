<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionsTableSeeder extends Seeder
{
    /**
     * @var string[][]
     */
    protected array $rolePermissions = [
        'admin' => [
            Permission::MANAGE_FUNDS,
            Permission::MANAGE_PROVIDERS,
            Permission::MANAGE_PRODUCTS,
            Permission::MANAGE_OFFICES,
            Permission::VIEW_FINANCES,
            Permission::VALIDATE_RECORDS,
            Permission::SCAN_VOUCHERS,
            Permission::MANAGE_PROVIDER_FUNDS,
            Permission::MANAGE_VOUCHERS,
            Permission::MANAGE_EMPLOYEES,
            Permission::MANAGE_ORGANIZATION,
            Permission::VIEW_IMPLEMENTATIONS,
            Permission::MANAGE_IMPLEMENTATION,
            Permission::MANAGE_IMPLEMENTATION_CMS,
            Permission::MANAGE_BANK_CONNECTIONS,
            Permission::MANAGE_TRANSACTION_BULKS,
            Permission::MANAGE_REIMBURSEMENTS,
            Permission::MANAGE_IMPLEMENTATION_NOTIFICATIONS,
            Permission::VIEW_FUNDS,
            Permission::VIEW_PERSON_BSN_DATA,
            Permission::MANAGE_FUND_TEXTS,
            Permission::MANAGE_VALIDATORS,
            Permission::MAKE_DIRECT_PAYMENTS,
            Permission::MANAGE_BI_CONNECTION,
            Permission::MANAGE_PAYMENT_METHODS,
            Permission::VIEW_FUNDS_EXTRA_PAYMENTS,
            Permission::MANAGE_PAYOUTS,
            Permission::VIEW_IDENTITIES,
            Permission::MANAGE_IDENTITIES,
        ],
        'finance' => [
            Permission::VIEW_FINANCES,
            Permission::MANAGE_VOUCHERS,
            Permission::MANAGE_REIMBURSEMENTS,
            Permission::MANAGE_ORGANIZATION,
            Permission::MANAGE_FUNDS,
            Permission::MANAGE_TRANSACTION_BULKS,
            Permission::MAKE_DIRECT_PAYMENTS,
            Permission::MANAGE_BANK_CONNECTIONS,
            Permission::MANAGE_PAYMENT_METHODS,
            Permission::VIEW_FUNDS_EXTRA_PAYMENTS,
            Permission::VIEW_IMPLEMENTATIONS,
        ],
        'validation' => [
            Permission::VALIDATE_RECORDS,
            Permission::VIEW_FUNDS,
            Permission::VIEW_PERSON_BSN_DATA,
        ],
        'supervisor_validator' => [
            Permission::MANAGE_VALIDATORS,
            Permission::VIEW_FUNDS,
        ],
        'policy_officer' => [
            Permission::VIEW_FUNDS,
            Permission::MANAGE_PROVIDERS,
            Permission::MANAGE_PRODUCTS,
            Permission::MANAGE_OFFICES,
            Permission::MANAGE_PROVIDER_FUNDS,
            Permission::VIEW_IMPLEMENTATIONS,
        ],
        'operation_officer' => [
            Permission::SCAN_VOUCHERS,
        ],
        'voucher_officer' => [
            Permission::MANAGE_FUNDS,
            Permission::MANAGE_VOUCHERS,
            Permission::VIEW_PERSON_BSN_DATA,
            Permission::MAKE_DIRECT_PAYMENTS,
            Permission::MANAGE_REIMBURSEMENTS,
            Permission::MANAGE_EMPLOYEES,
            Permission::VIEW_FINANCES,
            Permission::VIEW_IMPLEMENTATIONS,
        ],
        'implementation_communication_manager' => [
            Permission::VIEW_FUNDS,
            Permission::MANAGE_IMPLEMENTATION_CMS,
            Permission::MANAGE_IMPLEMENTATION_NOTIFICATIONS,
            Permission::MANAGE_FUND_TEXTS,
        ],
        'bank_manager' => [
            Permission::MANAGE_BANK_CONNECTIONS,
            Permission::MANAGE_TRANSACTION_BULKS,
            Permission::VIEW_FUNDS,
            Permission::VIEW_FINANCES,
            Permission::VIEW_IMPLEMENTATIONS,
        ],
        'finance_reader' => [
            Permission::VIEW_FINANCES,
        ],
        'voucher_reader' => [
            Permission::VIEW_VOUCHERS,
            Permission::VIEW_IMPLEMENTATIONS,
            Permission::VIEW_FUNDS,
        ],
        'payouts_manager' => [
            Permission::MANAGE_PAYOUTS,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        RolePermission::truncate();
        $roles = Role::pluck('id', 'key');
        $permissions = Permission::pluck('id', 'key');

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
