<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddFundBackofficeLogIdToVouchersTable
 * @noinspection PhpUnused
 */
class AddFundBackofficeLogIdToVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_backoffice_log_id')->nullable()->after('activation_code_uid');

            $table->foreign('fund_backoffice_log_id')
                ->references('id')->on('fund_backoffice_logs')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign('vouchers_fund_backoffice_log_id_foreign');
            $table->dropColumn('fund_backoffice_log_id');
        });
    }
}
