<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FundRequest;

class AddEmployeeIdToFundRequestRecordsTable extends Migration
{
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

        $fundRequests = FundRequest::get();

        foreach ($fundRequests as $fundRequest) {
            $fundRequest->records()->update([
                'employee_id' => $fundRequest->employee_id
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
}
