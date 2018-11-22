<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use App\Models\Organization;
use App\Models\Employee;
use App\Models\Role;

class DropProviderIdentitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('provider_identities')) {
            $providerIdentities = DB::table('provider_identities')->get();

            $roleId = Role::query()->where([
                'key' => 'operation_officer'
            ])->first()->id;

            $organizations = Organization::query()->whereIn(
                'id', $providerIdentities->pluck('provider_id')
            )->get()->keyBy('id');

            $providerIdentities = $providerIdentities->groupBy('provider_id');

            foreach($providerIdentities as $organizationId => $_providerIdentities) {
                /** @var Organization $organization */
                $organization = $organizations[$organizationId];

                foreach ($_providerIdentities as $_providerIdentity) {
                    /** @var Employee $employee */
                    $employee = $organization->employees()->firstOrCreate([
                        'identity_address' => $_providerIdentity->identity_address
                    ]);

                    $employee->roles()->attach($roleId);
                }
            }

            Schema::dropIfExists('provider_identities');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('provider_identities')) {
            (new CreateProviderIdentitiesTable())->up();

            if (Schema::hasTable('employees') && Schema::hasTable('employee_roles')) {
                $roleId = Role::query()->where([
                    'key' => 'operation_officer'
                ])->first()->id;

                $employeeRoles = DB::table('employee_roles')->where([
                    'role_id' => $roleId
                ])->get();

                $employeeRoles = $employeeRoles->map(function(
                    $employeeRole
                ) use ($roleId) {
                    $employee = DB::table('employees')->where([
                        'id' => $employeeRole->employee_id,
                    ])->first();

                    return [
                        "identity_address"  => $employee->identity_address,
                        "provider_id"       => $employee->organization_id
                    ];
                })->toArray();

                DB::table('provider_identities')->insert($employeeRoles);
                DB::table('employee_roles')->where([
                    'role_id' => $roleId
                ])->delete();
            }
        }
    }
}
