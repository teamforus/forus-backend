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
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->unsignedInteger('voucher_id')->nullable()->after('id')->change();
            $table->unsignedInteger('fund_request_id')->nullable()->after('voucher_id');

            $table->foreign('fund_request_id')
                ->references('id')
                ->on('fund_requests')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->dropForeign('physical_card_requests_fund_request_id_foreign');
            $table->dropColumn('fund_request_id');
        });
    }
};
