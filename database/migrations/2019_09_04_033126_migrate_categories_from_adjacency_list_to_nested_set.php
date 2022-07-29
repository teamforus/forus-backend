<?php

use App\Models\ProductCategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('product_categories', function(Blueprint $table) {
            $table->unsignedInteger('_rgt')->after('parent_id');
            $table->unsignedInteger('_lft')->after('parent_id');
        });

        if (ProductCategory::count() > 0) {
            ProductCategory::fixTree();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('product_categories', function(Blueprint $table) {
            $table->dropColumn('_lft');
            $table->dropColumn('_rgt');
        });
    }
};
