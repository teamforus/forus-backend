<?php

use App\Models\RecordType;

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
        $controlTypes = [
            RecordType::CONTROL_TYPE_TEXT,
            RecordType::CONTROL_TYPE_SELECT,
            RecordType::CONTROL_TYPE_CHECKBOX,
            RecordType::CONTROL_TYPE_DATE,
            RecordType::CONTROL_TYPE_NUMBER,
            RecordType::CONTROL_TYPE_STEP,
            RecordType::CONTROL_TYPE_CURRENCY,
        ];

        Schema::table('record_types', function (Blueprint $table) use ($controlTypes) {
            $table->enum('control_type', $controlTypes)
                ->default(RecordType::CONTROL_TYPE_TEXT)
                ->after('type');
        });

        $baseTypes = [
            RecordType::TYPE_BOOL => RecordType::CONTROL_TYPE_CHECKBOX,
            RecordType::TYPE_DATE => RecordType::CONTROL_TYPE_DATE,
            RecordType::TYPE_STRING => RecordType::CONTROL_TYPE_TEXT,
            RecordType::TYPE_EMAIL => RecordType::CONTROL_TYPE_TEXT,
            'bsn' => RecordType::CONTROL_TYPE_NUMBER,
            RecordType::TYPE_IBAN => RecordType::CONTROL_TYPE_TEXT,
            RecordType::TYPE_NUMBER => RecordType::CONTROL_TYPE_NUMBER,
            RecordType::TYPE_SELECT => RecordType::CONTROL_TYPE_SELECT,
            RecordType::TYPE_SELECT_NUMBER => RecordType::CONTROL_TYPE_SELECT,
        ];

        $keys = [
            'net_worth' => RecordType::CONTROL_TYPE_CURRENCY,
            'base_salary' => RecordType::CONTROL_TYPE_CURRENCY,
            'children_nth' => RecordType::CONTROL_TYPE_STEP,
            'waa_kind_0_tm_4_2021_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'waa_kind_4_tm_18_2021_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'eem_kind_0_tm_4_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'eem_kind_4_tm_12_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'eem_kind_12_tm_14_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'eem_kind_14_tm_18_eligible_nth' => RecordType::CONTROL_TYPE_STEP,
            'children' => RecordType::CONTROL_TYPE_CHECKBOX,
            'kindpakket_eligible' => RecordType::CONTROL_TYPE_CHECKBOX,
            'kindpakket_2018_eligible' => RecordType::CONTROL_TYPE_CHECKBOX,
            'tax_id' => RecordType::CONTROL_TYPE_NUMBER,
            'birth_date' => RecordType::CONTROL_TYPE_DATE,
        ];

        $baseType = RecordType::CONTROL_TYPE_TEXT;
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
