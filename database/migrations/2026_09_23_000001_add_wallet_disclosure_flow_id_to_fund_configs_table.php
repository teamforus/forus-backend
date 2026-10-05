<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->text('fund_request_intro')->nullable()->after('allow_fund_requests');
            $table->text('fund_request_intro_text')->nullable()->after('fund_request_intro');
            $table->unsignedBigInteger('wallet_disclosure_flow_id')->nullable()->after('allow_fund_request_prefill');
            $table->foreign('wallet_disclosure_flow_id')->references('id')->on('wallet_flows')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropForeign(['wallet_disclosure_flow_id']);
            $table->dropColumn('wallet_disclosure_flow_id', 'fund_request_intro', 'fund_request_intro_text');
        });
    }
};
