<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOfficeScheduleBreaks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('office_schedules', function(Blueprint $table) {
            $table->time('break_start_time')->nullable()->default(null)
                ->after('end_time');
            $table->time('break_end_time')->nullable()->default(null)
                ->after('break_start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('office_schedules', function(Blueprint $table) {
            $table->dropColumn('break_start_time');
            $table->dropColumn('break_end_time');
        });
    }
}
