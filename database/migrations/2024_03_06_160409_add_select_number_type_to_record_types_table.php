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
        Schema::table('record_types', function (Blueprint $table) {
            $recordTypes = ['string', 'select', 'select_number', 'number', 'iban', 'email', 'date', 'bool'];

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
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
