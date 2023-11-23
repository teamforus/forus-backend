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
        $recordTypes = ['string', 'select', 'number', 'iban', 'email', 'date', 'bool'];

        Schema::table('record_types', function (Blueprint $table) {
            $table->renameColumn('type', 'type_tmp');
        });

        Schema::table('record_types', function (Blueprint $table) use ($recordTypes) {
            $table->enum('type', $recordTypes)->default('string')->after('key');
        });

        DB::table('record_types')->update([
            'type' => DB::raw('`type_tmp`'),
        ]);

        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('type_tmp');
            $table->boolean('criteria')->default(false)->after('system');
        });

        $usedKeys = array_filter(array_unique(array_merge(
            DB::table('fund_criteria')->pluck('record_type_key')->toArray(),
            DB::table('fund_formulas')->pluck('record_type_key')->toArray(),
            DB::table('fund_formula_products')->pluck('record_type_key_multiplier')->toArray(),
        )));

        DB::table('record_types')->whereIn('key', ['uid', ...$usedKeys])->update([
            'criteria' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('criteria');
            $table->string('type', 200)->change();
        });
    }
};
