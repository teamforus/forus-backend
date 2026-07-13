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
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('show_fund_image_list')->default(true)->after('show_product_map');
            $table->boolean('show_fund_partners_page')->default(false)->after('show_fund_image_list');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn(['show_fund_image_list', 'show_fund_partners_page']);
        });
    }
};
