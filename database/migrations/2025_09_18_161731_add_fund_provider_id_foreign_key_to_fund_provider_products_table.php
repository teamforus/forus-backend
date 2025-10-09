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
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->foreign('fund_provider_id')
                ->references('id')
                ->on('fund_providers')
                ->onDelete('restrict');
        });

        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->enum('state', ['draft', 'error', 'pending', 'accepted', 'rejected'])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->dropForeign(['fund_provider_id']);
        });

        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->string('state')->default('draft')->change();
        });
    }
};
