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
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->smallInteger('order')->after('record_type_key')->nullable();
            $table->foreignId('fund_criteria_step_id')->after('order')->nullable();

            $table->foreign('fund_criteria_step_id')
                ->references('id')
                ->on('fund_criteria_steps')
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
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->dropForeign('fund_criteria_fund_criteria_step_id_foreign');
            $table->dropColumn('order', 'fund_criteria_step_id');
        });
    }
};
