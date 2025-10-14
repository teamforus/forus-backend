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
            $table->unsignedInteger('root_product_category_id')->nullable()->after('page_title_suffix');
            $table->foreign('root_product_category_id')->references('id')->on('product_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropForeign('implementations_root_product_category_id_foreign');
            $table->dropColumn('root_product_category_id');
        });
    }
};
