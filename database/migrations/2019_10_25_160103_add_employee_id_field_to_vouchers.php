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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('note');
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign('vouchers_employee_id_foreign');
            $table->dropColumn('employee_id');
        });
    }
};
