<?php

use App\Models\FundCriterion;
use App\Models\FundRequestRecord;
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
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->enum('source', ['brp', 'form'])->after('note')->default('form');
        });

        DB::table('fund_request_records')
            ->whereIn('fund_criterion_id', function ($query) {
                $query->select('id')
                    ->from('fund_criteria')
                    ->where('fill_type', FundCriterion::FILL_TYPE_PREFILL);
            })
            ->update(['source' => FundRequestRecord::SOURCE_BRP]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
