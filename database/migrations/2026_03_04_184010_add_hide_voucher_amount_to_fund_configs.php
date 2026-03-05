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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('hide_voucher_amount')->default(false)->after('criteria_label_requirement_show');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('hide_voucher_amount');
        });
    }
};
