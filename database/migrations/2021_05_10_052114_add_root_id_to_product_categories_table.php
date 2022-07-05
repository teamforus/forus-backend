<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductCategory;

/**
 * @noinspection PhpUnused
 */
class AddRootIdToProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->integer('root_id')->after('parent_id')->unsigned()->nullable();

            $table->foreign('root_id')->references('id')->on('product_categories')
                ->onDelete('cascade');
        });

        ProductCategory::whereIsRoot()->each(function(ProductCategory $category) {
            $category->descendants()->update([
                'root_id' => $category->id,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign('product_categories_root_id_foreign');
            $table->dropColumn('root_id');
        });
    }
}
