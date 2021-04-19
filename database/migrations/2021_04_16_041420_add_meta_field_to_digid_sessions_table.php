<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddMetaFieldToDigidSessionsTable
 * @noinspection PhpUnused
 */
class AddMetaFieldToDigidSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->json('meta')->after('identity_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
}
