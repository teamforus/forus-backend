<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFundIdToPrevalidations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->integer('fund_id')->unsigned()->nullable()->after('identity_address');
        });

        // roles with record validation permission
        $roles = Db::table('role_permissions')->where([
            'permission_id' => Db::table('permissions')->where([
                'key' => 'validate_records'
            ])->first()->id
        ])->pluck('role_id');

        // all system employees that have `validate_records` permission
        $employees = DB::table(
            'employee_roles'
        )->whereIn('role_id', $roles)->pluck('employee_id');

        DB::table('organizations')->get()->each(function(
            $organization
        ) use ($employees) {
            // add all employees prevalidations to first organization fund
            $fund = DB::table('funds')->where([
                'organization_id' => $organization->id
            ])->orderBy('created_at', 'ASC')->first();

            // organization dont have any funds
            if (!$fund) {
                return;
            }

            // identity_addresses that can validate records for the organization
            $validators = DB::table('employees')->where([
                'organization_id' => $organization->id
            ])->whereIn('id', $employees)->pluck('identity_address');

            // add organization owner
            $validators->push($organization->identity_address);

            // set fund_id on prevalidations
            DB::table('prevalidations')
                ->whereIn('identity_address', $validators)
                ->update([
                    'fund_id' => $fund->id
                ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->dropColumn('fund_id');
        });
    }
}
