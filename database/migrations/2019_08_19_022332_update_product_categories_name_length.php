<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProductCategoriesNameLength extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('key', 120)->change();
        });

        Schema::table('product_category_translations', function (Blueprint $table) {
            $table->string('name', 120)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('key', 20)->change();
        });

        Schema::table('product_category_translations', function (Blueprint $table) {
            $table->string('name', 20)->change();
        });
    }
}
