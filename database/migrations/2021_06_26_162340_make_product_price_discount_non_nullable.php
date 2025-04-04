<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Product::whereNull('price_discount')->update([
            'price_discount' => 0,
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_discount')->nullable()->default('0.0')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_discount')->nullable()->change();
        });
    }
};
