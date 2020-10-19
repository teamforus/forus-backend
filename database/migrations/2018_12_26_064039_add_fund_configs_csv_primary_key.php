<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFundConfigsCsvPrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->string('key')->unique()->change();
        });

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('csv_primary_key')->after('bunq_sandbox')->nullable();
            $table->foreign('csv_primary_key'
            )->references('key')->on('record_types')->onDelete('set null');
        });

        DB::table('fund_configs')->update([
            'csv_primary_key' => 'bsn'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function(Blueprint $table) {
            $table->dropForeign('fund_configs_csv_primary_key_foreign');
            $table->dropColumn('csv_primary_key');
        });

        Schema::table('record_types', function (Blueprint $table) {
            $table->dropUnique('record_types_key_unique');
        });
    }
}
