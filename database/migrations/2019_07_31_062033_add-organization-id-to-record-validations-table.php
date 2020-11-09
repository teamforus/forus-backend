<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrganizationIdToRecordValidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('record_validations', function(Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()
                ->after('identity_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_validations', function(Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
}
