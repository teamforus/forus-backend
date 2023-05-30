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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(['fund_id', 'product_id', 'parent_id']);
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
            $table->dropForeign('vouchers_fund_id_foreign');
            $table->dropIndex('vouchers_fund_id_product_id_parent_id_index');

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');
        });
    }
};
