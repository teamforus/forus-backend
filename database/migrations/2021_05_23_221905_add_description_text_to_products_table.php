<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;

/**
 * @noinspection PhpUnused
 */
class AddDescriptionTextToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text("description_text")->nullable()->default('')->after('description');
        });

        foreach (Product::get() as $product) {
            $product->update([
                'description_text' => $product->descriptionToText(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn("description_text");
        });
    }
}
