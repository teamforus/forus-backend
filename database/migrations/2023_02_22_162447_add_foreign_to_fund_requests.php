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
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->foreign('fund_id')
                ->references('id')->on('funds')->onDelete('cascade');

            $table->index('fund_id');
        });

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')->on('employees')->onDelete('set null');

            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropForeign(['fund_id']);
            $table->dropIndex(['fund_id']);
        });

        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id']);
        });
    }
};
