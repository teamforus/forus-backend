<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('identity_address');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });

        $this->fillEmployeeIds();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    /**
     * Fill the employee_id column in prevalidations table with matching employees based on organization_id and identity_address.
     */
    private function fillEmployeeIds(): void
    {
        $prevalidations = DB::table('prevalidations')
            ->whereNull('employee_id')
            ->get();

        foreach ($prevalidations as $prevalidation) {
            $employee = DB::table('employees')
                ->where('organization_id', $prevalidation->organization_id)
                ->where('identity_address', $prevalidation->identity_address)
                ->latest()
                ->first();

            if ($employee) {
                DB::table('prevalidations')
                    ->where('id', $prevalidation->id)
                    ->update(['employee_id' => $employee->id]);
            }
        }
    }
};
