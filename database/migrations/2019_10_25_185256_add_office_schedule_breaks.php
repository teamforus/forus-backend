<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
    public function down(): void
    {
        Schema::table('office_schedules', function(Blueprint $table) {
            $table->dropColumn('break_start_time');
            $table->dropColumn('break_end_time');
        });
    }
};
