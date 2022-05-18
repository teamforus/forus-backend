<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class FundsAddDefaultValidatorEmployeeField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('funds', function(Blueprint $table) {
            $table->unsignedInteger('default_validator_employee_id')->nullable();
            $table->boolean('auto_requests_validation')->default(false);

            $table->foreign('default_validator_employee_id'
            )->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', function(Blueprint $table) {
            $table->dropForeign('funds_default_validator_employee_id_foreign');
            $table->dropColumn('default_validator_employee_id');
            $table->dropColumn('auto_requests_validation');
        });
    }
}
