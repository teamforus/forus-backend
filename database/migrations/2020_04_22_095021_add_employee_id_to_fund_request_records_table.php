<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('state');
        });

        foreach (DB::table('fund_requests')->get() as $fundRequest) {
            DB::table('fund_request_records')->where('fund_request_id', $fundRequest->id)->update([
                'employee_id' => $fundRequest->employee_id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }
};
