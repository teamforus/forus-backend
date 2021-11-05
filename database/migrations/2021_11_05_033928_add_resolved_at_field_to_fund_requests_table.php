<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FundRequest;

class AddResolvedAtFieldToFundRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dateTime('resolved_at')->nullable()->after('updated_at');
        });

        foreach (FundRequest::get() as $fundRequest) {
            if (in_array($fundRequest->state, [FundRequest::STATE_APPROVED, FundRequest::STATE_DECLINED])) {
                $fundRequest->update([
                    'resolved_at' => $fundRequest->updated_at
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
}
