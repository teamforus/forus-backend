<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFooterFieldsToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->text('description_contact_details')->after('more_info_url')->nullable();
            $table->text('description_opening_times')->after('description_contact_details')->nullable();
            $table->string('privacy_statement_url', 100)->after('description_opening_times')->nullable();
            $table->string('terms_and_conditions_url', 100)->after('privacy_statement_url')->nullable();
            $table->string('accessibility_url', 100)->after('terms_and_conditions_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('description_contact_details');
            $table->dropColumn('description_opening_times');
            $table->dropColumn('privacy_statement_url');
            $table->dropColumn('terms_and_conditions_url');
            $table->dropColumn('accessibility_url');
        });

    }
}
