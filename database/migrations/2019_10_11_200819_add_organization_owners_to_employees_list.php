<?php

use App\Models\Role;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;

class AddOrganizationOwnersToEmployeesList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Organization::get()->each(function (Organization $organization) {
            $query = $organization->only('identity_address');

            /** @var \App\Models\Employee $employee */
            $employee = $organization->employees()->firstOrCreate($query);
            $employee->roles()->sync(Role::pluck('id'));

            if (method_exists($organization, 'validators')) {
                $organization->validators()->firstOrCreate($query);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
}
