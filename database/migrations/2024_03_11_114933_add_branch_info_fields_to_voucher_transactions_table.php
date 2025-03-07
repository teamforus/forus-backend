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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->string('branch_id', 20)->nullable()->after('employee_id');
            $table->string('branch_name', 100)->nullable()->after('branch_id');
            $table->decimal('branch_number', 12, 0)->nullable()->after('branch_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropColumn('branch_id');
            $table->dropColumn('branch_name');
            $table->dropColumn('branch_number');
        });
    }
};
