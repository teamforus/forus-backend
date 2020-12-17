<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddPrevalidationIdFieldToRecordValidationsTable
 * @noinspection PhpUnused
 */
class AddPrevalidationIdFieldToRecordValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_validations', function (Blueprint $table) {
            $table->unsignedInteger('prevalidation_id')->after('organization_id')->nullable();

            $table->foreign('prevalidation_id')->references('id')->on('prevalidations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_validations', static function (Blueprint $table) {
            $table->dropForeign('record_validations_prevalidation_id_foreign');
            $table->dropColumn('prevalidation_id');
        });
    }
}
