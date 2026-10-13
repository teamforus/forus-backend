<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_formulas', function (Blueprint $table) {
            $table->enum('type', ['multiply', 'fixed', 'multiply_vouchers'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('fund_formulas')
            ->where('type', 'multiply_vouchers')
            ->delete();

        Schema::table('fund_formulas', function (Blueprint $table) {
            $table->enum('type', ['multiply', 'fixed'])->change();
        });
    }
};
