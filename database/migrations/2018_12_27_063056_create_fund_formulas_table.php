<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundFormulasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_formulas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_id')->unsigned();
            $table->enum('type', [
                'multiply',
                'fixed'
            ]);
            $table->decimal('amount', 10, 2)->unsigned();
            $table->string('record_type_key', 200)->nullable();
            $table->timestamps();

            $table->foreign('record_type_key'
            )->references('key')->on('record_types')->onDelete('set null');

            $table->foreign('fund_id'
            )->references('id')->on('funds')->onDelete('cascade');
        });


        DB::table('fund_configs')->get()->each(function($fund_config) {
            if (DB::table('record_types')->where([
                    'key' => $fund_config->formula_multiplier
                ])->count() == 0) {
                return;
            }

            DB::table('fund_formulas')->insert([
                'fund_id'           => $fund_config->fund_id,
                'type'              => 'multiply',
                'amount'            => $fund_config->formula_amount,
                'record_type_key'   => $fund_config->formula_multiplier,
                'created_at'        => date('Y-m-d H:i:s')
            ]);
        });

        Schema::table('fund_configs', function(Blueprint $table) {
            $table->dropColumn('formula_amount');
            $table->dropColumn('formula_multiplier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_configs', function(Blueprint $table) {
            $table->decimal('formula_amount', 10, 2)->default(0)->after('bunq_sandbox');
            $table->string('formula_multiplier', 40)->default('')->after('formula_amount');
        });

        DB::table('fund_formulas')->where([
            'type' => 'multiply'
        ])->orderBy('id')->each(function($fund_formula) {
            DB::table('fund_configs')->where([
                'fund_id' => $fund_formula->fund_id
            ])->update([
                'formula_amount' => $fund_formula->amount,
                'formula_multiplier' => $fund_formula->record_type_key,
            ]);
        });

        Schema::dropIfExists('fund_formulas');
    }
}
