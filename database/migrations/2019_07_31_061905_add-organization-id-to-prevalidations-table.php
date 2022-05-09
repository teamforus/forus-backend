<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @noinspection PhpUnused
 */
class AddOrganizationIdToPrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->unsignedInteger('organization_id')->nullable()
                ->after('fund_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->dropColumn('organization_id');
        });
    }
}
