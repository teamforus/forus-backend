<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class AddBusinessTypeIdToOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->integer('business_type_id')->unsigned()->nullable()->after('website_public');

            $table->foreign('business_type_id'
            )->references('id')->on('business_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->dropForeign('organizations_business_type_id_foreign');
            $table->dropColumn('business_type_id');
        });
    }
}
