<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('allow_fund_request_prefill')->default(false)->after('allow_fund_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('allow_fund_request_prefill');
        });
    }
};
