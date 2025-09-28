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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(['parent_id', 'state', 'fund_id', 'created_at', 'expire_at'], 'idx_vouchers_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex('idx_vouchers_search');

            $table->foreign('parent_id')
                ->references('id')
                ->on('vouchers')
                ->onDelete('cascade');
        });
    }
};
