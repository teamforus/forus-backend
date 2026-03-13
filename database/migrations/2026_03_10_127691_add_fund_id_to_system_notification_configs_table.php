<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('system_notification_configs', function (Blueprint $table) {
            $table->unsignedInteger('fund_id')->nullable()->after('implementation_id');
            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');

            $table->unique([
                'implementation_id', 'system_notification_id', 'fund_id',
            ], 'system_notification_configs_scope_unique');

            $table->index([
                'implementation_id', 'system_notification_id', 'fund_id',
            ], 'system_notification_configs_scope_keys');
        });

        Schema::table('system_notification_configs', function (Blueprint $table) {
            $table->dropUnique('system_notification_configs_unique_keys');
            $table->dropIndex('system_notification_configs_index_keys');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('system_notification_configs', function (Blueprint $table) {
            $table->unique([
                'implementation_id', 'system_notification_id',
            ], 'system_notification_configs_unique_keys');

            $table->index([
                'implementation_id', 'system_notification_id',
            ], 'system_notification_configs_index_keys');
        });

        Schema::table('system_notification_configs', function (Blueprint $table) {
            $table->dropForeign(['fund_id']);
            $table->dropUnique('system_notification_configs_scope_unique');
            $table->dropIndex('system_notification_configs_scope_keys');
            $table->dropColumn('fund_id');
        });
    }
};
