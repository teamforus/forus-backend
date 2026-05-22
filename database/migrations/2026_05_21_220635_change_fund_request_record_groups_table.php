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
        Schema::rename('fund_request_record_groups', 'record_groups');
        Schema::rename('fund_request_record_group_records', 'record_group_keys');

        Schema::table('record_group_keys', function (Blueprint $table) {
            $table->dropForeign('group_records_record_group_id_foreign');
            $table->renameColumn('fund_request_record_group_id', 'record_group_id');

            $table
                ->foreign('record_group_id')
                ->references('id')
                ->on('record_groups')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('record_groups', 'fund_request_record_groups');
        Schema::rename('record_group_keys', 'fund_request_record_group_records');

        Schema::table('fund_request_record_group_records', function (Blueprint $table) {
            $table->dropForeign('record_group_keys_record_group_id_foreign');
            $table->renameColumn('record_group_id', 'fund_request_record_group_id');

            $table
                ->foreign('fund_request_record_group_id', 'group_records_record_group_id_foreign')
                ->references('id')
                ->on('fund_request_record_groups')
                ->onDelete('cascade');
        });
    }
};
