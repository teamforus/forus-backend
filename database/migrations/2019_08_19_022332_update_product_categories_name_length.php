<?php

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
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('key', 20)->change();
        });

        Schema::table('product_category_translations', function (Blueprint $table) {
            $table->string('name', 20)->change();
        });
    }
};
