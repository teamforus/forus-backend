<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Organization;
use App\Models\Employee;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        if (!Schema::hasTable('provider_identities')) {
            Schema::create('provider_identities', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('provider_id')->unsigned();
                $table->string('identity_address')->default('');
                $table->timestamps();

                $table->foreign('provider_id')
                    ->references('id')
                    ->on('organizations')
                    ->onDelete('cascade');
            });

            if (Schema::hasTable('employees') && Schema::hasTable('employee_roles')) {
                $roleId = Role::where('key', 'operation_officer')->first()->id;
                $employeeRoles = DB::table('employee_roles')->where('role_id', $roleId)->get();

                $employeeRoles = $employeeRoles->map(function($employeeRole) {
                    /** @var Employee $employee */
                    $employee = DB::table('employees')->where([
                        'id' => $employeeRole->employee_id,
                    ])->first();

                    return [
                        "identity_address"  => $employee->identity_address,
                        "provider_id"       => $employee->organization_id
                    ];
                })->toArray();

                DB::table('provider_identities')->insert($employeeRoles);
                DB::table('employee_roles')->where('role_id', $roleId)->delete();
            }
        }
    }
};
