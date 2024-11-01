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
        Schema::table('email_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('event_log_id')->after('id')->nullable();

            $table->string('from_name')->after('event_log_id')->nullable();
            $table->string('to_name')->after('from')->nullable();

            $table->dropColumn('cc');
            $table->dropColumn('bcc');

            $table->foreign('event_log_id')
                ->references('id')
                ->on('event_logs')
                ->onDelete('restrict');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->renameColumn('from', 'from_address');
            $table->renameColumn('to', 'to_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign('email_logs_event_log_id_foreign');
            $table->dropColumn('event_log_id');
            $table->dropColumn('from_name');
            $table->dropColumn('to_name');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->renameColumn('from_address', 'from');
            $table->renameColumn('to_address', 'to');
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('cc')->after('to')->nullable();
            $table->string('bcc')->after('cc')->nullable();
        });
    }
};
