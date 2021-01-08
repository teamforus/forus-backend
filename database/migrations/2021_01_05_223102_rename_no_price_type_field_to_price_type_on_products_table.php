<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;

/**
 * Class RenameNoPriceTypeFieldToPriceTypeOnProductsTable
 * @noinspection PhpUnused
 */
class RenameNoPriceTypeFieldToPriceTypeOnProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('no_price_discount', 'price_discount');
            $table->dropColumn('old_price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('price_type', [
                'free', 'regular', 'discount_percentage', 'discount_fixed',
            ])->after('no_price_type');
        });

        // set old `no_price` && `no_price_type` == 'discount' products
        // `price_type` to 'discount_percentage'
        Product::where([
            'no_price' => true,
            'no_price_type' => 'discount',
        ])->update([
            'price_type' => 'discount_percentage',
        ]);

        // set old `no_price` && `no_price_type` == 'free' products
        // `price_type` to 'discount_percentage'
        Product::where([
            'no_price' => true,
            'no_price_type' => 'free',
        ])->update([
            'price_type' => 'free',
        ]);

        // set old `no_price` products `price` to 0 just in case
        Product::where('no_price', true)->update([
            'price' => 0
        ]);

        // set old non `no_price` products `price_type` to `regular`
        Product::where('no_price', false)->update([
            'price_type' => 'regular',
        ]);

        // Drop no price column
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('no_price');
            $table->dropColumn('no_price_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('price_discount', 'no_price_discount');
            $table->boolean('no_price')->default(false)->after('unlimited_stock');
            $table->decimal('old_price', 8, 2)->nullable()->after('price');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->enum('no_price_type', [
                'free', 'discount',
            ])->after('price_type');
        });

        Product::where('price_type', '!=', 'regular')->update([
            'no_price' => true,
        ]);

        Product::where('price_type', 'discount_percentage')->update([
            'no_price_type' => 'discount',
        ]);

        Product::where('price_type', 'discount_fixed')->update([
            'no_price_type' => 'discount',
            'no_price_discount' => 0,
        ]);

        Product::where('price_type', 'free')->update([
            'no_price_type' => 'free',
            'no_price_discount' => 0,
        ]);

        Product::where('no_price_discount', '>', '100')->update([
            'no_price_discount' => 100,
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_type');
        });
    }
}
