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
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_relation_id')->nullable()->after('voucher_id');

            $table->foreign('voucher_relation_id')
                ->references('id')
                ->on('voucher_relations')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->dropForeign('fund_backoffice_logs_voucher_relation_id_foreign');
            $table->dropColumn('voucher_relation_id');
        });
    }
};
