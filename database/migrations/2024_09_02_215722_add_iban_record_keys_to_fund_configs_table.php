<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('iban_record_key')->nullable()->after('outcome_type');
            $table->string('iban_name_record_key')->nullable()->after('iban_record_key');

            $table
                ->foreign('iban_record_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('restrict');

            $table
                ->foreign('iban_name_record_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropForeign('fund_configs_iban_record_key_foreign');
            $table->dropForeign('fund_configs_iban_name_record_key_foreign');
            $table->dropColumn('iban_record_key', 'iban_name_record_key');
        });
    }
};
