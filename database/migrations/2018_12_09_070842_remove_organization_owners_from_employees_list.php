<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Remove organization owners from employees list
        Organization::all()->map(function (Organization $organization) {
            $organization->employees()->where([
                'identity_address' => $organization->identity_address,
            ])->delete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
