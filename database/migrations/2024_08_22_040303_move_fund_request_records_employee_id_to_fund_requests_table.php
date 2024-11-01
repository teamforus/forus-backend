<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('identity_address');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });

        $recordGroups = DB::table('fund_request_records')
            ->whereNotNull('employee_id')
            ->get()
            ->groupBy('fund_request_id');

        /**
         * @var int $requestId
         * @var \Illuminate\Support\Collection $recordGroup
         */
        foreach ($recordGroups as $requestId => $recordGroup) {
            $employees = $recordGroup->pluck('employee_id')->unique();

            if ($employees->count() !== 1) {
                throw new Exception('Invalid fund request record!');
            }

            DB::table('fund_requests')
                ->where('id', $requestId)
                ->update(['employee_id' => $employees[0]]);
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropForeign('fund_request_records_employee_id_foreign');
        });

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('state');

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('restrict');
        });

        $requests = DB::table('fund_requests')
            ->whereNotNull('employee_id')
            ->get();

        foreach ($requests as $request) {
            DB::table('fund_request_records')
                ->where('fund_request_id', $request->id)
                ->update(['employee_id' => $request->employee_id]);
        }

        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropForeign('fund_requests_employee_id_foreign');
        });

        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }
};
