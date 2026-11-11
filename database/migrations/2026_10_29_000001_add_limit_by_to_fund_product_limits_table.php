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
        Schema::table('fund_product_limits', function (Blueprint $table) {
            $table->enum('limit_by', ['products', 'amount'])->default('products')->after('state');
            $table->decimal('limit', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_product_limits', function (Blueprint $table) {
            $table->dropColumn('limit_by');
            $table->unsignedInteger('limit')->change();
        });
    }
};
