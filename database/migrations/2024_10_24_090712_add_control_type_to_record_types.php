<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $controlTypes = ['text', 'select', 'checkbox', 'date', 'number', 'step', 'currency'];

        Schema::table('record_types', function (Blueprint $table) use ($controlTypes) {
            $table->enum('control_type', $controlTypes)->default('text')->after('type');
        });

        $baseTypes = [
            'bool' => 'checkbox',
            'date' => 'date',
            'string' => 'text',
            'email' => 'text',
            'bsn' => 'number',
            'iban' => 'text',
            'number' => 'number',
            'select' => 'select',
            'select_number' => 'select',
        ];

        $keys = [
            'net_worth' => 'currency',
            'base_salary' => 'currency',
            'children_nth' => 'step',
            'waa_kind_0_tm_4_2021_eligible_nth' => 'step',
            'waa_kind_4_tm_18_2021_eligible_nth' => 'step',
            'eem_kind_0_tm_4_eligible_nth' => 'step',
            'eem_kind_4_tm_12_eligible_nth' => 'step',
            'eem_kind_12_tm_14_eligible_nth' => 'step',
            'eem_kind_14_tm_18_eligible_nth' => 'step',
            'children' => 'checkbox',
            'kindpakket_eligible' => 'checkbox',
            'kindpakket_2018_eligible' => 'checkbox',
            'tax_id' => 'number',
            'birth_date' => 'date',
        ];

        $baseType = 'text';
        $recordTypes = DB::table('record_types')->get();

        foreach ($recordTypes as $recordType) {
            $control_type = $keys[$recordType->key] ?? $baseTypes[$recordType->type] ?? $baseType;

            DB::table('record_types')
                ->where('id', $recordType->id)
                ->update(compact('control_type'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('control_type');
        });
    }
};
