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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->index(['voucher_id', 'target']);
            $table->index('target');
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
            $table->dropIndex('voucher_transactions_voucher_id_target_index');
            $table->dropIndex('voucher_transactions_target_index');
        });
    }
};
