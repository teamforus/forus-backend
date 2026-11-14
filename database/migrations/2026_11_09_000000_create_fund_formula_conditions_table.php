<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_formula_conditions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_formula_id')->nullable();
            $table->unsignedInteger('fund_formula_product_id')->nullable();
            $table->unsignedInteger('record_type_id');
            $table->enum('operator', ['=', '<', '<=', '>', '>=']);
            $table->string('value', 400);
            $table->timestamps();

            $table->foreign('fund_formula_id', 'formula_conditions_formula_fk')
                ->references('id')->on('fund_formulas')->restrictOnDelete();

            $table->foreign('fund_formula_product_id', 'formula_conditions_product_fk')
                ->references('id')->on('fund_formula_products')->restrictOnDelete();

            $table->foreign('record_type_id', 'formula_conditions_record_type_fk')
                ->references('id')->on('record_types')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE fund_formula_conditions ADD CONSTRAINT formula_conditions_one_parent_check '
            . 'CHECK ((fund_formula_id IS NULL) <> (fund_formula_product_id IS NULL))');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_formula_conditions');
    }
};
