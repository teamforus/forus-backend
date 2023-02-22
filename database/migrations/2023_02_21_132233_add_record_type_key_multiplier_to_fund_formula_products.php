<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_formula_products', function (Blueprint $table) {
            $table->string('record_type_key_multiplier', 200)->nullable()
                ->after('price');

            $table->foreign('record_type_key_multiplier')
                ->references('key')->on('record_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_formula_products', function (Blueprint $table) {
            $table->dropForeign(['record_type_key_multiplier']);
            $table->dropColumn('record_type_key_multiplier');
        });
    }
};
