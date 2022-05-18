<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddLimitsAndAmountToFundProviderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_provider_products', static function (Blueprint $table) {
            $table->unsignedInteger('limit_total')->nullable()->after('product_id');
            $table->unsignedInteger('limit_per_identity')->nullable()->after('limit_total');
            $table->decimal('amount', 8, 2)->nullable()->after('limit_per_identity');
            $table->softDeletes()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_provider_products', static function (Blueprint $table) {
            $table->dropColumn('limit_total');
            $table->dropColumn('limit_per_identity');
            $table->dropColumn('amount');
            $table->dropSoftDeletes();
        });
    }
}
