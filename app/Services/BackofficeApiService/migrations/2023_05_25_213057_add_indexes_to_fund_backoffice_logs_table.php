<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->unsignedInteger('voucher_id')->nullable()->change();
            $table->index('state');

            $table->foreign('voucher_id')->references('id')->on('vouchers')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->dropForeign('fund_backoffice_logs_voucher_id_foreign');
            $table->dropIndex('fund_backoffice_logs_state_index');
        });
    }
};
